<?php

namespace Tests\Feature\Localization;

use Tests\TestCase;

class LocalizationTest extends TestCase
{
    public function test_arabic_is_served_at_the_root_right_to_left(): void
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('<html lang="ar" dir="rtl"', false)
            ->assertSee('bootstrap-5.3.8/css/bootstrap.rtl.min.css', false)
            ->assertDontSee('bootstrap-5.3.8/css/bootstrap.min.css', false)
            ->assertSee(__('front.nav.home', [], 'ar'));
    }

    public function test_english_is_served_under_the_en_prefix_left_to_right(): void
    {
        $this->refreshApplicationWithLocale('en');

        $response = $this->get('/en');

        $response->assertOk()
            ->assertSee('<html lang="en" dir="ltr"', false)
            ->assertSee('bootstrap-5.3.8/css/bootstrap.min.css', false)
            ->assertDontSee('bootstrap-5.3.8/css/bootstrap.rtl.min.css', false)
            ->assertSee('Home');
    }

    public function test_the_arabic_prefix_redirects_to_the_unprefixed_url(): void
    {
        $this->refreshApplicationWithLocale('ar');

        $response = $this->get('/ar');

        $response->assertRedirect();
        $this->assertSame('/', parse_url($response->headers->get('Location'), PHP_URL_PATH));
    }

    public function test_the_browser_language_does_not_redirect_the_arabic_root(): void
    {
        $this->get('/', ['Accept-Language' => 'en-US,en;q=0.9'])
            ->assertOk()
            ->assertSee('<html lang="ar"', false);
    }

    public function test_arabic_pages_link_to_the_english_version(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('href="http://localhost/en"', false)
            ->assertSee('hreflang="en"', false);
    }

    public function test_english_pages_link_to_the_arabic_version(): void
    {
        $this->refreshApplicationWithLocale('en');

        $this->get('/en')
            ->assertOk()
            ->assertSee('href="http://localhost/" class="yq-lang-switch" hreflang="ar"', false)
            ->assertDontSee('href="http://localhost/ar"', false)
            ->assertSee('العربية');
    }

    public function test_navigation_only_lists_pages_that_exist(): void
    {
        // Catalog/about/contact routes arrive in later phases; until then they are not linked.
        $this->get('/')
            ->assertOk()
            ->assertDontSee(__('front.nav.about', [], 'ar'))
            ->assertDontSee(__('front.nav.contact', [], 'ar'));
    }

    public function test_module_translations_are_namespaced_and_do_not_override_core_files(): void
    {
        app()->setLocale('ar');

        $this->assertSame('تسجيل الدخول', __('auth::auth.login_title'));
        $this->assertSame('بيانات الدخول غير صحيحة.', __('auth.failed'));
    }
}
