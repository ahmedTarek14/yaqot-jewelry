<?php

namespace Tests\Feature\Assets;

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
        $this->assertLocalAssetsExist($this->get('/'));
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
            'assets/admin/vendor/soft-ui-dashboard-1.1.0/LICENSE',
            'assets/fonts/ibm-plex-sans-arabic/OFL.txt',
            'assets/fonts/ibm-plex-sans/OFL.txt',
            'assets/fonts/amiri/OFL.txt',
            'assets/fonts/cormorant-garamond/OFL.txt',
        ] as $licence) {
            $this->assertFileExists(public_path($licence));
        }
    }

    private function assertLocalAssetsExist(TestResponse $response): void
    {
        $response->assertOk();

        preg_match_all('/(?:href|src)="([^"]+)"/', $response->getContent(), $matches);

        $assets = collect($matches[1])
            ->map(fn (string $url) => parse_url($url, PHP_URL_PATH))
            ->filter(fn (?string $path) => $path && str_starts_with($path, '/assets/'))
            ->unique();

        $this->assertNotEmpty($assets, 'The page references no local assets.');

        foreach ($assets as $path) {
            $this->assertFileExists(public_path(ltrim($path, '/')), "Missing asset: $path");
        }
    }
}
