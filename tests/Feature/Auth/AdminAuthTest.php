<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Models\Admin;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Admin login/logout under both dashboard locales (/ar/admin/..., /en/admin/...).
 * The unprefixed /admin redirects are covered in tests/Feature/Admin.
 */
class AdminAuthTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, array{string}> */
    public static function locales(): array
    {
        return ['arabic' => ['ar'], 'english' => ['en']];
    }

    #[DataProvider('locales')]
    public function test_guests_are_redirected_from_the_dashboard_to_the_login_page(string $locale): void
    {
        $this->refreshApplicationWithLocale($locale);

        $this->get("/$locale/admin")->assertRedirect("/$locale/admin/login");
    }

    #[DataProvider('locales')]
    public function test_the_login_page_is_translated(string $locale): void
    {
        $this->refreshApplicationWithLocale($locale);

        $this->get("/$locale/admin/login")
            ->assertOk()
            ->assertSee(__('auth::auth.login_title', [], $locale))
            ->assertSee(__('auth::auth.submit', [], $locale))
            ->assertSee('action="http://localhost/'.$locale.'/admin/login"', false);
    }

    #[DataProvider('locales')]
    public function test_an_admin_can_log_in_and_reach_the_dashboard(string $locale): void
    {
        $this->refreshApplicationWithLocale($locale);
        $admin = Admin::factory()->create(['password' => 'secret-password']);

        $this->post(route('admin.login.submit'), [
            'email' => $admin->email,
            'password' => 'secret-password',
        ])->assertRedirect("/$locale/admin");

        $this->assertAuthenticatedAs($admin, 'admin');

        $this->get("/$locale/admin")
            ->assertOk()
            ->assertSee('<html lang="'.$locale.'"', false)
            ->assertSee(__('home::home.dashboard.welcome', ['name' => $admin->name], $locale));
    }

    #[DataProvider('locales')]
    public function test_wrong_credentials_are_rejected(string $locale): void
    {
        $this->refreshApplicationWithLocale($locale);
        $admin = Admin::factory()->create(['password' => 'secret-password']);

        $this->from("/$locale/admin/login")
            ->post(route('admin.login.submit'), ['email' => $admin->email, 'password' => 'wrong-password'])
            ->assertRedirect("/$locale/admin/login")
            ->assertSessionHasErrors(['email' => __('auth.failed', [], $locale)]);

        $this->assertGuest('admin');
    }

    /** @return array<string, array{string, string}> */
    public static function throttleMessages(): array
    {
        return [
            'arabic' => ['ar', 'محاولات دخول كثيرة'],
            'english' => ['en', 'Too many login attempts'],
        ];
    }

    #[DataProvider('throttleMessages')]
    public function test_login_is_throttled_after_five_failed_attempts(string $locale, string $message): void
    {
        $this->refreshApplicationWithLocale($locale);
        $admin = Admin::factory()->create(['password' => 'secret-password']);

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('admin.login.submit'), ['email' => $admin->email, 'password' => 'wrong']);
        }

        // Even the right password is refused while locked out.
        $this->post(route('admin.login.submit'), ['email' => $admin->email, 'password' => 'secret-password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest('admin');
        $this->assertStringContainsString($message, session('errors')->first('email'));
    }

    #[DataProvider('locales')]
    public function test_an_authenticated_admin_visiting_login_is_sent_to_the_dashboard(string $locale): void
    {
        $this->refreshApplicationWithLocale($locale);

        $this->actingAs(Admin::factory()->create(), 'admin')
            ->get("/$locale/admin/login")
            ->assertRedirect("/$locale/admin");
    }

    #[DataProvider('locales')]
    public function test_an_admin_can_log_out(string $locale): void
    {
        $this->refreshApplicationWithLocale($locale);

        $this->actingAs(Admin::factory()->create(), 'admin')
            ->post(route('admin.logout'))
            ->assertRedirect("/$locale/admin/login");

        $this->assertGuest('admin');
    }

    public function test_the_login_and_logout_urls_are_localized(): void
    {
        $this->refreshApplicationWithLocale('en');

        $this->assertSame('http://localhost/en/admin/login', route('admin.login.submit'));
        $this->assertSame('http://localhost/en/admin/logout', route('admin.logout'));
    }

    #[DataProvider('locales')]
    public function test_a_user_from_the_default_guard_cannot_access_the_dashboard(string $locale): void
    {
        $this->refreshApplicationWithLocale($locale);

        $this->actingAs(User::factory()->create())
            ->get("/$locale/admin")
            ->assertRedirect("/$locale/admin/login");
    }

    public function test_there_is_no_public_registration_or_password_reset(): void
    {
        $this->get('/register')->assertNotFound();
        $this->post('/register')->assertNotFound();
        $this->get('/admin/register')->assertNotFound();
        $this->get('/password/reset')->assertNotFound();

        foreach (['ar', 'en'] as $locale) {
            $this->refreshApplicationWithLocale($locale);

            $this->get("/$locale/admin/register")->assertNotFound();
            $this->get("/$locale/admin/password/reset")->assertNotFound();
            $this->get("/$locale/register")->assertNotFound();
        }
    }
}
