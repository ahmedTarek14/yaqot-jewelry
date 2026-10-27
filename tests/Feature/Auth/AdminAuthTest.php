<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Models\Admin;
use Tests\TestCase;

class AdminAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_the_dashboard_to_the_login_page(): void
    {
        $this->get('/admin')->assertRedirect(route('admin.login'));
    }

    public function test_the_login_page_is_arabic_right_to_left(): void
    {
        $this->get(route('admin.login'))
            ->assertOk()
            ->assertSee('<html lang="ar" dir="rtl"', false)
            ->assertSee('soft-ui-dashboard-rtl.min.css', false)
            ->assertSee(__('auth::auth.login_title', [], 'ar'));
    }

    public function test_an_admin_can_log_in_and_reach_the_dashboard(): void
    {
        $admin = Admin::factory()->create(['password' => 'secret-password']);

        $this->post(route('admin.login.submit'), [
            'email' => $admin->email,
            'password' => 'secret-password',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($admin, 'admin');

        $this->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('<html lang="ar" dir="rtl"', false)
            ->assertSee($admin->name);
    }

    public function test_wrong_credentials_are_rejected(): void
    {
        $admin = Admin::factory()->create(['password' => 'secret-password']);

        $this->from(route('admin.login'))
            ->post(route('admin.login.submit'), ['email' => $admin->email, 'password' => 'wrong-password'])
            ->assertRedirect(route('admin.login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest('admin');
    }

    public function test_login_is_throttled_after_five_failed_attempts(): void
    {
        $admin = Admin::factory()->create(['password' => 'secret-password']);

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('admin.login.submit'), ['email' => $admin->email, 'password' => 'wrong']);
        }

        // Even the right password is refused while locked out.
        $this->post(route('admin.login.submit'), ['email' => $admin->email, 'password' => 'secret-password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest('admin');
        $this->assertStringContainsString(
            'محاولات دخول كثيرة',
            session('errors')->first('email')
        );
    }

    public function test_an_authenticated_admin_visiting_login_is_sent_to_the_dashboard(): void
    {
        $this->actingAs(Admin::factory()->create(), 'admin')
            ->get(route('admin.login'))
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_an_admin_can_log_out(): void
    {
        $this->actingAs(Admin::factory()->create(), 'admin')
            ->post(route('admin.logout'))
            ->assertRedirect(route('admin.login'));

        $this->assertGuest('admin');
    }

    public function test_a_user_from_the_default_guard_cannot_access_the_dashboard(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/admin')
            ->assertRedirect(route('admin.login'));
    }

    public function test_there_is_no_public_registration_or_password_reset(): void
    {
        $this->get('/register')->assertNotFound();
        $this->post('/register')->assertNotFound();
        $this->get('/admin/register')->assertNotFound();
        $this->get('/password/reset')->assertNotFound();
    }
}
