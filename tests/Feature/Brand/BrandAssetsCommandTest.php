<?php

namespace Tests\Feature\Brand;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Runs yaqot:brand-assets on a small synthetic logo in a temporary directory,
 * so the real public/ files are never touched.
 */
class BrandAssetsCommandTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dir = storage_path('framework/testing/brand-'.uniqid());
        File::ensureDirectoryExists($this->dir.'/out');
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->dir);

        parent::tearDown();
    }

    public function test_it_generates_trimmed_logos_favicons_and_a_manifest(): void
    {
        $source = $this->fixture();

        $this->runCommand($source)->assertSuccessful();

        $manifest = json_decode(file_get_contents("{$this->dir}/out/manifest.json"), true);

        $this->assertSame(sha1_file($source), $manifest['source']['sha1']);
        // 200x200 canvas, opaque content spans x 40-159, y 20-169 -> trimmed to 120x150.
        $this->assertSame(['width' => 120, 'height' => 150], $manifest['source']['trimmed']);

        foreach (['logo-h56.webp', 'logo-h112.webp', 'logo-h64.webp', 'logo-h128.webp', 'logo-h88.webp', 'logo-h104.webp'] as $file) {
            $this->assertFileExists("{$this->dir}/out/$file");
            $this->assertSame((int) substr($file, 6, -5), getimagesize("{$this->dir}/out/$file")[1], "$file has the wrong height");
        }
    }

    public function test_it_never_upscales_the_logo(): void
    {
        // Trimmed source is 150px tall: 176px and 208px variants would be upscaled.
        $this->runCommand($this->fixture())
            ->expectsOutputToContain('Skipped logo-h176.webp')
            ->expectsOutputToContain('Skipped logo-h208.webp')
            ->assertSuccessful();

        $this->assertFileDoesNotExist("{$this->dir}/out/logo-h176.webp");
        $this->assertFileDoesNotExist("{$this->dir}/out/logo-h208.webp");
    }

    public function test_favicons_use_the_mark_without_the_text_row_and_keep_their_padding(): void
    {
        $this->runCommand($this->fixture())->assertSuccessful();

        foreach ([16 => 1, 32 => 1] as $size => $padding) {
            $box = $this->opaqueBox("{$this->dir}/out/favicon-$size.png");

            $this->assertSame([$size, $size], [$box['w'], $box['h']]);
            $this->assertGreaterThanOrEqual($padding, $box['x0'], "favicon-$size touches the left edge");
            $this->assertGreaterThanOrEqual($padding, $box['y0'], "favicon-$size touches the top edge");
            $this->assertLessThanOrEqual($size - 1 - $padding, $box['x1'], "favicon-$size touches the right edge");
            $this->assertLessThanOrEqual($size - 1 - $padding, $box['y1'], "favicon-$size touches the bottom edge");
        }

        // The mark (rows 20-119, 120x100) is wider than tall: a square favicon of it
        // has transparent rows above and below; the text row is not included.
        $box = $this->opaqueBox("{$this->dir}/out/favicon-32.png");
        $this->assertLessThan(30, $box['y1'] - $box['y0'] + 1);

        $touch = getimagesize("{$this->dir}/out/apple-touch-icon.png");
        $this->assertSame([180, 180], [$touch[0], $touch[1]]);
    }

    public function test_it_writes_a_valid_ico_with_three_sizes(): void
    {
        $this->runCommand($this->fixture())->assertSuccessful();

        $ico = file_get_contents("{$this->dir}/favicon.ico");
        $header = unpack('vreserved/vtype/vcount', substr($ico, 0, 6));

        $this->assertSame(['reserved' => 0, 'type' => 1, 'count' => 3], $header);

        $sizes = [];
        for ($i = 0; $i < 3; $i++) {
            $entry = unpack('Cwidth/Cheight/Ccolors/Creserved/vplanes/vbpp/Vbytes/Voffset', substr($ico, 6 + 16 * $i, 16));
            $this->assertSame("\x89PNG", substr($ico, $entry['offset'], 4));
            $sizes[] = $entry['width'];
        }

        $this->assertSame([16, 32, 48], $sizes);
    }

    public function test_it_fails_cleanly_when_the_source_is_missing(): void
    {
        $this->runCommand("{$this->dir}/missing.png")->assertFailed();

        $this->assertFileDoesNotExist("{$this->dir}/out/manifest.json");
    }

    private function runCommand(string $source)
    {
        return $this->artisan('yaqot:brand-assets', [
            '--source' => $source,
            '--output' => "{$this->dir}/out",
            '--favicon' => "{$this->dir}/favicon.ico",
        ]);
    }

    /**
     * 200x200 transparent PNG: a 120x100 "mark" (rows 20-119), a transparent
     * gap, then a 120x30 "text" row (rows 140-169) — the logo's layout.
     */
    private function fixture(): string
    {
        $image = imagecreatetruecolor(200, 200);
        imagealphablending($image, false);
        imagesavealpha($image, true);
        imagefill($image, 0, 0, imagecolorallocatealpha($image, 0, 0, 0, 127));

        $gold = imagecolorallocatealpha($image, 210, 162, 73, 0);
        imagefilledrectangle($image, 40, 20, 159, 119, $gold);
        imagefilledrectangle($image, 40, 140, 159, 169, $gold);

        $path = "{$this->dir}/logo.png";
        imagepng($image, $path);

        return $path;
    }

    /** @return array{w: int, h: int, x0: int, y0: int, x1: int, y1: int} */
    private function opaqueBox(string $png): array
    {
        $image = imagecreatefrompng($png);
        $w = imagesx($image);
        $h = imagesy($image);
        $box = ['w' => $w, 'h' => $h, 'x0' => $w, 'y0' => $h, 'x1' => -1, 'y1' => -1];

        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                if (((imagecolorat($image, $x, $y) >> 24) & 0x7F) < 120) {
                    $box['x0'] = min($box['x0'], $x);
                    $box['y0'] = min($box['y0'], $y);
                    $box['x1'] = max($box['x1'], $x);
                    $box['y1'] = max($box['y1'], $y);
                }
            }
        }

        return $box;
    }
}
