<?php

namespace Tests\Feature\Security;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Mcamara\LaravelLocalization\LaravelLocalization;
use Modules\Setting\Services\SettingService;
use Tests\TestCase;

/**
 * Security headers on every response (global middleware): pages, unknown URLs, the admin login.
 */
class SecurityHeadersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (getenv(LaravelLocalization::ENV_ROUTE_KEY) === false) {
            $this->refreshApplicationWithLocale('en');
        }

        // The CSP is off while debugging (the debug error page needs inline scripts).
        config(['app.debug' => false]);
    }

    public function test_pages_unknown_urls_and_the_admin_login_carry_the_headers(): void
    {
        foreach (['/en', '/en/this-page-does-not-exist', '/en/admin/login'] as $url) {
            $this->get($url)
                ->assertHeader('X-Content-Type-Options', 'nosniff')
                ->assertHeader('X-Frame-Options', 'DENY')
                ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
                ->assertHeader('Cross-Origin-Opener-Policy', 'same-origin')
                ->assertHeaderMissing('X-Powered-By');

            $this->assertStringContainsString('camera=()', $this->get($url)->headers->get('Permissions-Policy'), $url);
        }
    }

    public function test_the_csp_only_allows_this_site_and_a_nonce_for_the_one_inline_script(): void
    {
        $response = $this->get('/en')->assertOk();
        $csp = $response->headers->get('Content-Security-Policy');

        $this->assertStringContainsString("default-src 'self'", $csp);
        $this->assertStringContainsString("object-src 'none'", $csp);
        $this->assertStringContainsString("frame-ancestors 'none'", $csp);
        $this->assertStringContainsString("base-uri 'self'", $csp);
        $this->assertStringNotContainsString("'unsafe-inline'", preg_replace("/style-src-attr 'unsafe-inline'/", '', $csp));
        $this->assertStringNotContainsString('unsafe-eval', $csp);
        $this->assertStringNotContainsString('google', $csp, 'Google is not allowed without an analytics id');

        // The page's inline script carries this response's nonce, and it is a fresh one each time.
        preg_match("/'nonce-([^']+)'/", $csp, $header);
        $this->assertNotEmpty($header);
        $this->assertStringContainsString('<script nonce="'.$header[1].'">', $response->getContent());

        preg_match("/'nonce-([^']+)'/", $this->get('/en')->headers->get('Content-Security-Policy'), $second);
        $this->assertNotSame($header[1], $second[1]);

        // Every <script> in the page is either external or carries the nonce.
        preg_match_all('/<script(?![^>]*\bsrc=)(?![^>]*type="application\/(ld\+)?json")[^>]*>/', $response->getContent(), $inline);
        foreach ($inline[0] as $tag) {
            $this->assertStringContainsString('nonce="'.$header[1].'"', $tag);
        }
    }

    public function test_google_is_allowed_only_on_pages_that_carry_the_analytics_notice(): void
    {
        $csp = fn (string $url) => $this->get($url)->headers->get('Content-Security-Policy');

        app(SettingService::class)->setMany(['ga_measurement_id' => 'G-ABC123XYZ9']);

        $withGoogle = $csp('/en');
        $this->assertStringContainsString('script-src \'self\' \'nonce-', $withGoogle);
        $this->assertStringContainsString('https://www.googletagmanager.com', $withGoogle);
        $this->assertStringContainsString('https://*.google-analytics.com', $withGoogle);

        // The admin never loads Google, so its pages never allow it.
        $this->assertStringNotContainsString('google', (string) $csp('/en/admin/login'));

        app(SettingService::class)->setMany(['ga_measurement_id' => '']);
        $this->assertStringNotContainsString('google', $csp('/en'));
    }

    public function test_hsts_is_sent_over_https_only(): void
    {
        $this->get('http://localhost/en')->assertHeaderMissing('Strict-Transport-Security');

        $secure = $this->get('https://localhost/en')->assertHeader('Strict-Transport-Security', 'max-age=15552000');
        $this->assertStringNotContainsString('includeSubDomains', $secure->headers->get('Strict-Transport-Security'));
        $this->assertStringContainsString('upgrade-insecure-requests', $secure->headers->get('Content-Security-Policy'));

        // Behind a proxy that ends TLS the forwarded protocol says so.
        $this->get('/en', ['X-Forwarded-Proto' => 'https'])->assertHeader('Strict-Transport-Security');
    }

    public function test_the_production_session_cookie_is_secure_http_only_and_lax(): void
    {
        // What .env.production.example sets: SESSION_SECURE_COOKIE=true, SESSION_HTTP_ONLY=true, SESSION_SAME_SITE=lax.
        config(['session.secure' => true, 'session.http_only' => true, 'session.same_site' => 'lax']);

        $cookies = $this->get('https://localhost/en')->headers->getCookies();
        $session = collect($cookies)->first(fn ($cookie) => $cookie->getName() === config('session.cookie'));

        $this->assertNotNull($session);
        $this->assertTrue($session->isSecure());
        $this->assertTrue($session->isHttpOnly());
        $this->assertSame('lax', $session->getSameSite());
    }

    public function test_the_csp_allows_blob_images_for_the_admin_previews_and_nothing_else_exotic(): void
    {
        $csp = $this->get('/en/admin/login')->headers->get('Content-Security-Policy');

        $this->assertStringContainsString("img-src 'self' data: blob:", $csp);
        $this->assertStringNotContainsString('*', preg_replace('#https://\*\.[a-z.-]+#', '', $csp), 'no bare wildcard sources');
    }

    public function test_there_is_no_csp_while_debugging(): void
    {
        config(['app.debug' => true]);

        $this->get('/en')->assertHeaderMissing('Content-Security-Policy');
    }

    public function test_files_that_are_not_pages_get_no_csp(): void
    {
        $this->get('/robots.txt')->assertHeaderMissing('Content-Security-Policy')->assertHeader('X-Content-Type-Options', 'nosniff');
    }
}
