<?php

namespace Modules\Home\Console;

use App\View\Components\Logo;
use GdImage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\ImageInterface;

/**
 * Regenerates every brand image from public/assets/images/brand/logo.png
 * (the single source of truth): display WebPs, favicons and a manifest with
 * their dimensions. The output is committed; deploys do not run this.
 */
class BrandAssetsCommand extends Command
{
    protected $signature = 'yaqot:brand-assets
        {--source= : Source logo (default: public/assets/images/brand/logo.png)}
        {--output= : Directory for generated files (default: public/assets/images/brand/generated)}
        {--favicon= : Path of the favicon.ico to write (default: public/favicon.ico)}';

    protected $description = 'Generate logo display sizes and favicons from the brand logo';

    private const SOURCE = 'assets/images/brand/logo.png';

    private const MARK_BACKGROUND = '#101010';

    /**
     * GD alpha runs from 0 (opaque) to 127 (transparent). Pixels at 120+ (under
     * ~6% opacity) are invisible anti-aliasing noise in the source and count as
     * transparent, so stray specks do not widen the trim box.
     */
    private const TRANSPARENT = 120;

    private ImageManager $manager;

    /** @var array<string, array{width: int, height: int, bytes: int}> */
    private array $written = [];

    public function handle(): int
    {
        $source = $this->option('source') ?: public_path(self::SOURCE);
        $outDir = rtrim($this->option('output') ?: public_path(Logo::GENERATED_DIR), '/\\');
        $favicon = $this->option('favicon') ?: public_path('favicon.ico');

        if (! is_file($source)) {
            $this->error("Missing $source");

            return self::FAILURE;
        }

        $this->manager = new ImageManager(new Driver);
        File::ensureDirectoryExists($outDir);

        $original = $this->manager->read($source);

        // 1. Trim transparent padding.
        [$x0, $y0, $x1, $y1] = $this->opaqueBox($this->gd($original), 0, $original->height() - 1);
        $logo = (clone $original)->crop($x1 - $x0 + 1, $y1 - $y0 + 1, $x0, $y0);
        $this->line(sprintf('Source %dx%d, trimmed to %dx%d', $original->width(), $original->height(), $logo->width(), $logo->height()));

        // 2. Display copies (WebP) at 1x and 2x of each display height.
        foreach (Logo::FILE_HEIGHTS as $height) {
            foreach ([$height, $height * 2] as $target) {
                if ($target > $logo->height()) {
                    $this->warn("Skipped logo-h{$target}.webp: taller than the trimmed source ({$logo->height()}px).");

                    continue;
                }

                $this->save((clone $logo)->scale(height: $target)->toWebp(quality: 90), "$outDir/logo-h{$target}.webp");
            }
        }

        // 3. The calligraphy mark without the "JEWELRY" row, for favicons.
        $mark = $this->markOnly($logo);

        $icons = [];
        foreach ([16, 32, 48] as $size) {
            $png = (string) $this->square($mark, $size, max(1, (int) round($size / 32)))->toPng();
            $icons[$size] = $png;

            if ($size !== 48) {
                $this->save($png, "$outDir/favicon-$size.png", $size, $size);
            }
        }

        $touch = $this->manager->create(180, 180)->fill(self::MARK_BACKGROUND)
            ->place($this->square($mark, 144, 0), 'center');
        $this->save($touch->toPng(), "$outDir/apple-touch-icon.png");

        $this->save($this->ico($icons), $favicon, 48, 48);

        // 4. Manifest read by <x-logo />.
        $manifest = [
            'source' => [
                'file' => $this->option('source') ? basename($source) : self::SOURCE,
                'sha1' => sha1_file($source),
                'width' => $original->width(),
                'height' => $original->height(),
                'trimmed' => ['width' => $logo->width(), 'height' => $logo->height()],
            ],
            'files' => collect($this->written)
                ->filter(fn ($info, $path) => str_starts_with($path, $outDir))
                ->mapWithKeys(fn ($info, $path) => [basename($path) => $info])
                ->all(),
        ];
        File::put("$outDir/manifest.json", json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");

        $this->newLine();
        $this->table(['File', 'Size', 'KB'], collect($this->written)->map(fn ($info, $path) => [
            str_replace(public_path().DIRECTORY_SEPARATOR, '', $path),
            "{$info['width']}x{$info['height']}",
            number_format($info['bytes'] / 1024, 1),
        ])->values()->all());
        $this->info('Wrote '.count($this->written).' files and '.Logo::GENERATED_DIR.'/manifest.json');

        return self::SUCCESS;
    }

    /**
     * Crop the mark above the last fully transparent horizontal band in the
     * lower part of the logo (the gap above "JEWELRY"), then trim it.
     */
    private function markOnly(ImageInterface $logo): ImageInterface
    {
        $gd = $this->gd($logo);
        $width = $logo->width();
        $height = $logo->height();

        $cut = null;
        for ($y = $height - 1; $y >= (int) ($height * 0.6); $y--) {
            if ($this->rowIsTransparent($gd, $y, $width)) {
                $cut = $y;
                // Walk to the top of this transparent band.
                while ($cut > 0 && $this->rowIsTransparent($gd, $cut - 1, $width)) {
                    $cut--;
                }
                break;
            }
        }

        if ($cut === null) {
            $this->warn('No transparent band found above a text row; favicons use the whole logo.');

            return clone $logo;
        }

        [$x0, $y0, $x1, $y1] = $this->opaqueBox($gd, 0, $cut - 1);
        $this->line(sprintf('Mark: rows 0-%d of the trimmed logo, %dx%d', $cut - 1, $x1 - $x0 + 1, $y1 - $y0 + 1));

        return (clone $logo)->crop($x1 - $x0 + 1, $y1 - $y0 + 1, $x0, $y0);
    }

    /**
     * Fit $image into a transparent $size square with $padding pixels on each side.
     * scaleDown() and pad() only ever shrink, so a small source is centred, never enlarged.
     */
    private function square(ImageInterface $image, int $size, int $padding): ImageInterface
    {
        $inner = $size - 2 * $padding;

        return (clone $image)
            ->scaleDown($inner, $inner)
            ->pad($size, $size, 'rgba(0, 0, 0, 0)');
    }

    /** ICO container with embedded PNG images (supported by all current browsers). */
    private function ico(array $pngs): string
    {
        $header = pack('vvv', 0, 1, count($pngs));
        $entries = '';
        $data = '';
        $offset = 6 + 16 * count($pngs);

        foreach ($pngs as $size => $png) {
            // Width/height 0 means 256; bit depth 32; colour planes 1.
            $entries .= pack('CCCCvvVV', $size % 256, $size % 256, 0, 0, 1, 32, strlen($png), $offset + strlen($data));
            $data .= $png;
        }

        return $header.$entries.$data;
    }

    /** @return array{int, int, int, int} x0, y0, x1, y1 of non-transparent pixels within rows $fromY..$toY */
    private function opaqueBox(GdImage $gd, int $fromY, int $toY): array
    {
        $width = imagesx($gd);
        $x0 = $width;
        $y0 = null;
        $x1 = -1;
        $y1 = $fromY;

        for ($y = $fromY; $y <= $toY; $y++) {
            for ($x = 0; $x < $width; $x++) {
                if (((imagecolorat($gd, $x, $y) >> 24) & 0x7F) < self::TRANSPARENT) {
                    $y0 ??= $y;
                    $y1 = $y;
                    $x0 = min($x0, $x);
                    $x1 = max($x1, $x);
                }
            }
        }

        if ($y0 === null) {
            return [0, $fromY, $width - 1, $toY];
        }

        return [$x0, $y0, $x1, $y1];
    }

    private function rowIsTransparent(GdImage $gd, int $y, int $width): bool
    {
        for ($x = 0; $x < $width; $x++) {
            if (((imagecolorat($gd, $x, $y) >> 24) & 0x7F) < self::TRANSPARENT) {
                return false;
            }
        }

        return true;
    }

    private function gd(ImageInterface $image): GdImage
    {
        return $image->core()->native();
    }

    private function save(mixed $encoded, string $path, ?int $width = null, ?int $height = null): void
    {
        $bytes = (string) $encoded;
        File::put($path, $bytes);

        if ($width === null) {
            [$width, $height] = getimagesizefromstring($bytes);
        }

        $this->written[$path] = ['width' => $width, 'height' => $height, 'bytes' => strlen($bytes)];
    }
}
