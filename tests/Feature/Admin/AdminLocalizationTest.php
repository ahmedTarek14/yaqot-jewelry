<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Modules\Auth\Models\Admin;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The dashboard is served in Arabic (RTL) under /ar/admin and English (LTR)
 * under /en/admin; unprefixed /admin URLs redirect to the last used locale.
 */
class AdminLocalizationTest extends TestCase
{
    use RefreshDatabase;

    private const RTL_CSS = 'soft-ui-dashboard-1.1.0/css/soft-ui-dashboard-rtl.min.css';

    private const LTR_CSS = 'soft-ui-dashboard-1.1.0/css/soft-ui-dashboard.min.css';

    /** @return array<string, array{string, string, string}> */
    public static function locales(): array
    {
        // locale, direction, the other locale
        return [
            'arabic' => ['ar', 'rtl', 'en'],
            'english' => ['en', 'ltr', 'ar'],
        ];
    }

    /* ------------------------------------------------- Direction & theme CSS */

    #[DataProvider('locales')]
    public function test_the_login_page_uses_the_locale_direction_and_theme_build(string $locale, string $dir): void
    {
        $this->refreshApplicationWithLocale($locale);

        $this->assertDirection($this->get("/$locale/admin/login"), $locale, $dir);
    }

    #[DataProvider('locales')]
    public function test_the_dashboard_uses_the_locale_direction_and_theme_build(string $locale, string $dir): void
    {
        $this->refreshApplicationWithLocale($locale);

        $response = $this->actingAs(Admin::factory()->create(), 'admin')->get("/$locale/admin");

        $this->assertDirection($response, $locale, $dir);
        $response->assertSee(__('admin.navbar.logout', [], $locale))
            ->assertSee(__('admin.nav.dashboard', [], $locale));
    }

    /* ------------------------------------------------------- Language switch */

    #[DataProvider('locales')]
    public function test_the_login_page_links_to_itself_in_the_other_language(string $locale, string $dir, string $other): void
    {
        $this->refreshApplicationWithLocale($locale);

        $this->get("/$locale/admin/login")
            ->assertOk()
            ->assertSee('href="http://localhost/'.$other.'/admin/login" hreflang="'.$other.'"', false)
            ->assertSee('class="yq-auth__lang', false)
            ->assertSee('<span lang="'.$other.'">', false)
            ->assertSee('aria-label="'.e($this->switchLabel($locale, $other)).'"', false)
            ->assertDontSee('hreflang="'.$locale.'"', false);
    }

    #[DataProvider('locales')]
    public function test_the_dashboard_links_to_itself_in_the_other_language(string $locale, string $dir, string $other): void
    {
        $this->refreshApplicationWithLocale($locale);

        $this->actingAs(Admin::factory()->create(), 'admin')
            ->get("/$locale/admin")
            ->assertOk()
            ->assertSee('href="http://localhost/'.$other.'/admin" hreflang="'.$other.'"', false)
            ->assertSee('yq-lang-switch', false)
            ->assertSee('aria-label="'.e($this->switchLabel($locale, $other)).'"', false);
    }

    #[DataProvider('locales')]
    public function test_the_view_site_button_opens_the_site_in_the_dashboard_language(string $locale, string $dir, string $other): void
    {
        $this->refreshApplicationWithLocale($locale);

        $this->actingAs(Admin::factory()->create(), 'admin')
            ->get("/$locale/admin")
            ->assertOk()
            ->assertSee('href="http://localhost/'.$locale.'" class="btn btn-sm btn-outline-dark', false)
            ->assertDontSee('href="http://localhost/'.$other.'" class="btn', false);
    }

    /* ---------------------------------------------------- Remembered locale */

    #[DataProvider('locales')]
    public function test_visiting_a_localized_admin_page_remembers_its_locale(string $locale): void
    {
        $this->refreshApplicationWithLocale($locale);

        $this->get("/$locale/admin/login")->assertSessionHas('admin_locale', $locale);
    }

    /* ----------------------------------------------- Unprefixed /admin URLs */

    public function test_unprefixed_admin_urls_default_to_arabic(): void
    {
        $this->get('/admin')->assertRedirect('http://localhost/ar/admin');
        $this->get('/admin/login')->assertRedirect('http://localhost/ar/admin/login');
    }

    public function test_unprefixed_admin_urls_follow_the_remembered_locale(): void
    {
        $this->withSession(['admin_locale' => 'en'])
            ->get('/admin')
            ->assertRedirect('http://localhost/en/admin');
    }

    public function test_an_invalid_remembered_locale_falls_back_to_arabic(): void
    {
        $this->withSession(['admin_locale' => 'fr'])
            ->get('/admin')
            ->assertRedirect('http://localhost/ar/admin');
    }

    public function test_the_unprefixed_redirect_keeps_the_raw_query_string(): void
    {
        $response = $this->withSession(['admin_locale' => 'en'])->get('/admin/login?tag=1&tag=2&x=y');

        $this->assertSame('http://localhost/en/admin/login?tag=1&tag=2&x=y', $response->headers->get('Location'));
    }

    public function test_unprefixed_admin_posts_are_not_handled(): void
    {
        $admin = Admin::factory()->create(['password' => 'secret-password']);

        $this->post('/admin/login', ['email' => $admin->email, 'password' => 'secret-password'])->assertNotFound();
        $this->assertGuest('admin');

        $this->actingAs($admin, 'admin')->post('/admin/logout')->assertNotFound();
        $this->assertAuthenticatedAs($admin, 'admin');
    }

    public function test_logging_in_after_switching_language_lands_in_the_new_language(): void
    {
        // A guest bounced from /en/admin switches to Arabic on the login page, then logs in.
        $this->refreshApplicationWithLocale('ar');
        $admin = Admin::factory()->create(['password' => 'secret-password']);

        $this->withSession(['url.intended' => 'http://localhost/en/admin'])
            ->post(route('admin.login.submit'), ['email' => $admin->email, 'password' => 'secret-password'])
            ->assertRedirect('http://localhost/ar/admin');
    }

    public function test_the_language_switch_keeps_the_raw_query_string(): void
    {
        $this->refreshApplicationWithLocale('en');

        $this->get('/en/admin/login?b=2&a=1&a=3')
            ->assertOk()
            ->assertSee('href="http://localhost/ar/admin/login?b=2&amp;a=1&amp;a=3"', false);
    }

    public function test_layout_data_is_rebuilt_for_every_request(): void
    {
        // Two requests in one application: the second page must not reuse the first page's links.
        $this->refreshApplicationWithLocale('en');

        $this->get('/en/admin/login')->assertSee('href="http://localhost/ar/admin/login"', false);

        $this->actingAs(Admin::factory()->create(), 'admin')
            ->get('/en/admin')
            ->assertOk()
            ->assertSee('href="http://localhost/ar/admin" hreflang="ar"', false)
            ->assertDontSee('href="http://localhost/ar/admin/login"', false);
    }

    private function switchLabel(string $locale, string $other): string
    {
        $native = config("laravellocalization.supportedLocales.$other.native");

        return __('admin.language.switch_to', ['language' => $native], $locale);
    }

    private function assertDirection(TestResponse $response, string $locale, string $dir): void
    {
        $response->assertOk()->assertSee('<html lang="'.$locale.'" dir="'.$dir.'"', false);

        if ($dir === 'rtl') {
            $response->assertSee(self::RTL_CSS, false)->assertDontSee(self::LTR_CSS, false);
        } else {
            $response->assertSee(self::LTR_CSS, false)->assertDontSee(self::RTL_CSS, false);
        }
    }
}
