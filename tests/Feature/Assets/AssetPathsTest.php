<?php

namespace Tests\Feature\Assets;

use App\View\Components\Logo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Modules\Auth\Models\Admin;
use Modules\Brand\Models\Brand;
use Modules\Category\Models\Category;
use Modules\Collection\Models\Collection;
use Modules\Home\Support\HeroSlides;
use Modules\Product\Models\Product;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * There is no asset build step: every CSS/JS/font file is committed under
 * public/assets. These tests make sure nothing a page references is missing.
 */
class AssetPathsTest extends TestCase
{
    use RefreshDatabase;

    public function test_assets_on_the_arabic_home_page_exist(): void
    {
        $this->refreshApplicationWithLocale('ar');

        $this->assertLocalAssetsExist($this->get('/ar'));
    }

    public function test_assets_on_the_english_home_page_exist(): void
    {
        $this->refreshApplicationWithLocale('en');

        $this->assertLocalAssetsExist($this->get('/en'));
    }

    /** @return array<string, array{string}> */
    public static function publicLocales(): array
    {
        return ['arabic' => ['ar'], 'english' => ['en']];
    }

    #[DataProvider('publicLocales')]
    public function test_assets_on_the_catalog_exist(string $locale): void
    {
        $this->refreshApplicationWithLocale($locale);

        $this->assertLocalAssetsExist($this->get("/$locale/catalog"));
    }

    #[DataProvider('publicLocales')]
    public function test_assets_on_the_phase_4b_pages_exist(string $locale): void
    {
        $this->refreshApplicationWithLocale($locale);

        $category = Category::factory()->create();
        $collection = Collection::factory()->create();
        $brand = Brand::factory()->create();
        $product = Product::factory()->visible()->create(['brand_id' => $brand->id]);
        $product->categories()->attach($category);
        $product->collections()->attach($collection);

        foreach ([
            "products/{$product->slug}",
            "categories/{$category->slug}",
            "collections/{$collection->slug}",
            "brands/{$brand->slug}",
            'about',
            'contact',
            'privacy-policy',
        ] as $path) {
            $this->assertLocalAssetsExist($this->get("/$locale/$path")->assertOk());
        }
    }

    /** @return array<string, array{string}> */
    public static function adminLocales(): array
    {
        // Each admin locale loads a different Soft UI build (RTL / LTR).
        return ['arabic' => ['ar'], 'english' => ['en']];
    }

    #[DataProvider('adminLocales')]
    public function test_assets_on_the_login_page_exist(string $locale): void
    {
        $this->refreshApplicationWithLocale($locale);

        $this->assertLocalAssetsExist($this->get("/$locale/admin/login"));
    }

    #[DataProvider('adminLocales')]
    public function test_assets_on_the_dashboard_exist(string $locale): void
    {
        $this->refreshApplicationWithLocale($locale);

        $response = $this->actingAs(Admin::factory()->create(), 'admin')->get("/$locale/admin");

        $this->assertLocalAssetsExist($response);
    }

    public function test_files_referenced_from_stylesheets_exist(): void
    {
        $stylesheets = array_merge(
            glob(public_path('assets/css/*.css')),
            glob(public_path('assets/admin/css/*.css')),
        );

        $this->assertNotEmpty($stylesheets);

        foreach ($stylesheets as $stylesheet) {
            preg_match_all('/url\(\s*["\']?(?!data:)([^"\')]+)["\']?\s*\)/', file_get_contents($stylesheet), $matches);

            foreach ($matches[1] as $reference) {
                $path = realpath(dirname($stylesheet).'/'.parse_url($reference, PHP_URL_PATH));

                $this->assertNotFalse($path, basename($stylesheet)." references a missing file: $reference");
            }
        }
    }

    /**
     * Phase 5b: the hero's fixed images. Each cutout keeps its PNG source and has transparent
     * WebP versions at 1x and 2x, at the widths HeroSlides advertises in srcset.
     */
    public function test_the_hero_images_exist_with_their_declared_sizes_and_keep_their_alpha(): void
    {
        $dir = public_path('assets/images/hero');

        foreach (HeroSlides::IMAGES as $image) {
            $this->assertFileExists("$dir/{$image['name']}.png");

            foreach ([1 => $image['widths'][0], 2 => $image['widths'][1]] as $scale => $width) {
                $file = "$dir/{$image['name']}-{$scale}x.webp";

                $this->assertFileExists($file);
                [$w, $h] = getimagesize($file);
                $this->assertSame([$width, $width], [$w, $h], basename($file));
                // VP8X header: bit 4 of the flags byte says the file carries an alpha channel.
                $this->assertNotSame(0, ord(substr(file_get_contents($file, false, null, 20, 1), 0, 1)) & 0x10, basename($file).' lost its alpha');
            }
        }

        foreach (['silk-2k', 'silk-4k', 'silk-mobile'] as $background) {
            $this->assertFileExists("$dir/$background.webp");
        }
    }

    public function test_bundled_third_party_assets_ship_with_their_licences(): void
    {
        foreach ([
            'assets/vendor/bootstrap-5.3.8/LICENSE',
            'assets/vendor/jquery-3.7.1/LICENSE.txt',
            'assets/admin/vendor/soft-ui-dashboard-1.1.0/LICENSE',
            'assets/fonts/ibm-plex-sans-arabic/OFL.txt',
            'assets/fonts/ibm-plex-sans/OFL.txt',
            'assets/fonts/amiri/OFL.txt',
            'assets/fonts/cormorant-garamond/OFL.txt',
        ] as $licence) {
            $this->assertFileExists(public_path($licence));
        }
    }

    public function test_generated_brand_assets_match_the_current_logo(): void
    {
        $dir = public_path('assets/images/brand/generated');
        $manifest = json_decode(file_get_contents("$dir/manifest.json"), true);

        $this->assertSame(
            sha1_file(public_path('assets/images/brand/logo.png')),
            $manifest['source']['sha1'],
            'logo.png changed: run php artisan yaqot:brand-assets and commit the output.'
        );

        foreach (array_keys($manifest['files']) as $file) {
            $this->assertFileExists("$dir/$file");
        }

        foreach (Logo::FILE_HEIGHTS as $height) {
            $this->assertArrayHasKey("logo-h{$height}.webp", $manifest['files']);
            $this->assertArrayHasKey('logo-h'.($height * 2).'.webp', $manifest['files']);
        }

        $this->assertFileExists(public_path('favicon.ico'));
    }

    private function assertLocalAssetsExist(TestResponse $response): void
    {
        $response->assertOk();

        $html = $response->getContent();
        preg_match_all('/(?:href|src)="([^"]+)"/', $html, $matches);
        preg_match_all('/srcset="([^"]+)"/', $html, $srcsets);

        // srcset="a.webp?v=1 1x, b.webp?v=1 2x" -> each URL
        $srcsetUrls = collect($srcsets[1])
            ->flatMap(fn (string $set) => explode(',', $set))
            ->map(fn (string $candidate) => strtok(trim($candidate), ' '));

        $assets = collect($matches[1])
            ->merge($srcsetUrls)
            ->map(fn (string $url) => parse_url($url, PHP_URL_PATH))
            ->filter(fn (?string $path) => $path && str_starts_with($path, '/assets/'))
            ->unique();

        $this->assertNotEmpty($assets, 'The page references no local assets.');

        foreach ($assets as $path) {
            $this->assertFileExists(public_path(ltrim($path, '/')), "Missing asset: $path");
        }
    }
}
