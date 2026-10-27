<?php

namespace Tests\Feature\Assets;

use App\View\Components\Logo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Modules\Auth\Models\Admin;
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

    public function test_assets_on_the_login_page_exist(): void
    {
        $this->assertLocalAssetsExist($this->get(route('admin.login')));
    }

    public function test_assets_on_the_dashboard_exist(): void
    {
        $response = $this->actingAs(Admin::factory()->create(), 'admin')->get(route('admin.dashboard'));

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
