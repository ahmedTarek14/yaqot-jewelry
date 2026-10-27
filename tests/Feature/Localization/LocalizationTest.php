<?php

namespace Tests\Feature\Localization;

use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class LocalizationTest extends TestCase
{
    public function test_the_root_redirects_to_the_english_home_page(): void
    {
        $this->assertRedirectsToPath($this->get('/'), '/en');
    }

    public function test_the_redirect_keeps_the_raw_query_string(): void
    {
        // Repeated keys and their order survive (fullUrl() would sort and collapse them).
        $response = $this->get('/?tag=1&tag=2&utm_source=test');

        $this->assertRedirectsToPath($response, '/en');
        $this->assertStringEndsWith('/en?tag=1&tag=2&utm_source=test', $response->headers->get('Location'));
    }

    public function test_the_browser_language_does_not_change_the_root_redirect(): void
    {
        $this->assertRedirectsToPath($this->get('/', ['Accept-Language' => 'ar-EG,ar;q=0.9']), '/en');
    }

    public function test_english_is_served_under_en_left_to_right(): void
    {
        $this->refreshApplicationWithLocale('en');

        $this->get('/en')
            ->assertOk()
            ->assertSee('<html lang="en" dir="ltr"', false)
            ->assertSee('bootstrap-5.3.8/css/bootstrap.min.css', false)
            ->assertDontSee('bootstrap-5.3.8/css/bootstrap.rtl.min.css', false)
            ->assertSee('Home');
    }

    public function test_arabic_is_served_under_ar_right_to_left(): void
    {
        $this->refreshApplicationWithLocale('ar');

        $this->get('/ar')
            ->assertOk()
            ->assertSee('<html lang="ar" dir="rtl"', false)
            ->assertSee('bootstrap-5.3.8/css/bootstrap.rtl.min.css', false)
            ->assertDontSee('bootstrap-5.3.8/css/bootstrap.min.css', false)
            ->assertSee(__('front.nav.home', [], 'ar'));
    }

    public function test_arabic_pages_link_to_the_english_version(): void
    {
        $this->refreshApplicationWithLocale('ar');

        $this->get('/ar')
            ->assertOk()
            ->assertSee('href="http://localhost/en" class="yq-lang-switch" hreflang="en"', false);
    }

    public function test_english_pages_link_to_the_arabic_version(): void
    {
        $this->refreshApplicationWithLocale('en');

        $this->get('/en')
            ->assertOk()
            ->assertSee('href="http://localhost/ar" class="yq-lang-switch" hreflang="ar"', false)
            ->assertSee('العربية');
    }

    public function test_pages_declare_hreflang_alternates_with_an_english_x_default(): void
    {
        $this->refreshApplicationWithLocale('ar');

        $this->get('/ar')
            ->assertOk()
            ->assertSee('<link rel="alternate" hreflang="ar" href="http://localhost/ar">', false)
            ->assertSee('<link rel="alternate" hreflang="en" href="http://localhost/en">', false)
            ->assertSee('<link rel="alternate" hreflang="x-default" href="http://localhost/en">', false);
    }

    public function test_hreflang_alternates_on_english_pages_ignore_the_query_string(): void
    {
        $this->refreshApplicationWithLocale('en');

        $this->get('/en?utm_source=newsletter')
            ->assertOk()
            ->assertSee('<link rel="alternate" hreflang="ar" href="http://localhost/ar">', false)
            ->assertSee('<link rel="alternate" hreflang="en" href="http://localhost/en">', false)
            ->assertSee('<link rel="alternate" hreflang="x-default" href="http://localhost/en">', false)
            ->assertDontSee('hreflang="ar" href="http://localhost/ar?', false);
    }

    public function test_admin_pages_link_to_the_arabic_site(): void
    {
        $this->get(route('admin.login'))
            ->assertOk()
            ->assertSee('href="http://localhost/ar" class="yq-auth__back', false);
    }

    public function test_navigation_only_lists_pages_that_exist(): void
    {
        // Catalog/about/contact routes arrive in later phases; until then they are not linked.
        $this->refreshApplicationWithLocale('ar');

        $this->get('/ar')
            ->assertOk()
            ->assertDontSee(__('front.nav.about', [], 'ar'))
            ->assertDontSee(__('front.nav.contact', [], 'ar'));
    }

    public function test_translatable_models_fall_back_to_english(): void
    {
        $this->assertSame('en', config('translatable.fallback_locale'));
        $this->assertSame(['ar', 'en'], config('translatable.locales'));
    }

    public function test_module_translations_are_namespaced_and_do_not_override_core_files(): void
    {
        app()->setLocale('ar');

        $this->assertSame('تسجيل الدخول', __('auth::auth.login_title'));
        $this->assertSame('بيانات الدخول غير صحيحة.', __('auth.failed'));
    }

    private function assertRedirectsToPath(TestResponse $response, string $path): void
    {
        $response->assertRedirect();
        $this->assertSame($path, parse_url($response->headers->get('Location'), PHP_URL_PATH));
    }
}
