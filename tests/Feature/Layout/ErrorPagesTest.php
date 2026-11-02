<?php

namespace Tests\Feature\Layout;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Mcamara\LaravelLocalization\LaravelLocalization;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

/**
 * The 404, 419, 429, 500 and 503 pages: in the site's style, in both languages at once, and
 * never a stack trace or a message from the failure.
 */
class ErrorPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (getenv(LaravelLocalization::ENV_ROUTE_KEY) === false) {
            $this->refreshApplicationWithLocale('en');
        }

        config(['app.debug' => false]);

        Route::get('/en/_boom/{code}', fn (int $code) => $code === 500
            ? throw new RuntimeException('secret database password hunter2 in /var/www/app/Secret.php')
            : abort($code));
    }

    /** @return array<string, array{int}> */
    public static function codes(): array
    {
        return ['not found' => [404], 'page expired' => [419], 'too many requests' => [429], 'server error' => [500], 'unavailable' => [503]];
    }

    #[DataProvider('codes')]
    public function test_each_error_page_shows_both_languages_in_the_site_style(int $code): void
    {
        $response = $this->get("/en/_boom/$code")->assertStatus($code);

        $response
            ->assertSee('class="yq-error__code"', false)
            ->assertSee((string) $code)
            ->assertSee('rel="stylesheet"', false)
            ->assertSee('name="robots" content="noindex"', false)
            ->assertSee(__("errors.codes.$code.title", [], 'en'))
            ->assertSee(__("errors.codes.$code.title", [], 'ar'))
            ->assertSee(__("errors.codes.$code.message", [], 'ar'))
            ->assertSee('lang="ar" dir="rtl"', false)
            ->assertSee('lang="en" dir="ltr"', false);
    }

    public function test_the_language_in_the_url_comes_first_and_links_home_in_that_language(): void
    {
        $this->refreshApplicationWithLocale('ar');
        Route::get('/ar/_boom', fn () => abort(404));

        $html = $this->get('/ar/_boom')->assertNotFound()->getContent();

        $this->assertStringContainsString('<html lang="ar" dir="rtl"', $html);
        $this->assertLessThan(strpos($html, 'Page not found'), strpos($html, __('errors.codes.404.title', [], 'ar')));
        $this->assertStringContainsString('href="http://localhost/ar"', $html);

        // An unknown URL without a language prefix falls back to the site's default, English.
        $plain = $this->get('/no-such-page')->assertNotFound()->getContent();
        $this->assertStringContainsString('<html lang="en" dir="ltr"', $plain);
        $this->assertStringContainsString('href="http://localhost/en"', $plain);
    }

    public function test_a_server_error_leaks_nothing(): void
    {
        $html = $this->get('/en/_boom/500')->assertStatus(500)->getContent();

        foreach (['hunter2', 'Secret.php', 'RuntimeException', 'Stack trace', '/var/www', 'vendor/laravel', 'Whoops', 'APP_KEY'] as $leak) {
            $this->assertStringNotContainsString($leak, $html);
        }
    }

    public function test_the_error_page_works_without_the_database(): void
    {
        // It uses no settings, branches or menus: dropping the schema must not break it.
        foreach (['setting_translations', 'settings', 'branch_translations', 'branches'] as $table) {
            Schema::dropIfExists($table);
        }

        $this->get('/en/_boom/404')->assertNotFound()->assertSee(__('errors.codes.404.title', [], 'en'));
        $this->get('/en/_boom/500')->assertStatus(500)->assertSee(__('errors.codes.500.title', [], 'en'));
    }

    public function test_the_security_headers_are_on_error_pages_too(): void
    {
        $this->get('/en/_boom/404')
            ->assertNotFound()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY');
    }

    public function test_the_maintenance_page_is_the_503_page(): void
    {
        $this->artisan('down')->assertSuccessful();

        try {
            $this->get('/en')
                ->assertStatus(503)
                ->assertSee(__('errors.codes.503.title', [], 'en'))
                // Answered by an earlier global middleware, yet still with the security headers.
                ->assertHeader('X-Content-Type-Options', 'nosniff')
                ->assertHeader('X-Frame-Options', 'DENY');
        } finally {
            $this->artisan('up');
        }
    }
}
