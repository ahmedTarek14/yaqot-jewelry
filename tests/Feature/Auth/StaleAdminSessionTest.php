<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Http\Middleware\EndStaleAdminSession;
use Modules\Auth\Models\Admin;
use Tests\TestCase;

/**
 * A session opened before the admin's password changed ends on its next request.
 */
class StaleAdminSessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_session_with_the_current_password_fingerprint_keeps_working(): void
    {
        $this->refreshApplicationWithLocale('en');
        $admin = Admin::factory()->create();

        $this->actingAs($admin, 'admin')
            ->withSession([EndStaleAdminSession::SESSION_KEY => EndStaleAdminSession::fingerprint($admin)])
            ->get('/en/admin')
            ->assertOk();
    }

    public function test_the_first_request_remembers_the_fingerprint(): void
    {
        $this->refreshApplicationWithLocale('en');
        $admin = Admin::factory()->create();

        $this->actingAs($admin, 'admin')->get('/en/admin')->assertOk();

        $this->assertSame(EndStaleAdminSession::fingerprint($admin), session(EndStaleAdminSession::SESSION_KEY));
    }

    public function test_a_session_from_before_a_password_change_is_logged_out(): void
    {
        $this->refreshApplicationWithLocale('en');
        $admin = Admin::factory()->create(['password' => 'Old-Passw0rd!!']);
        $before = EndStaleAdminSession::fingerprint($admin);

        // Another admin (or the owner) sets a new password.
        $admin->update(['password' => 'New-Passw0rd!!']);

        $this->actingAs($admin->fresh(), 'admin')
            ->withSession([EndStaleAdminSession::SESSION_KEY => $before])
            ->get('/en/admin')
            ->assertRedirect(route('admin.login'));

        $this->assertGuest('admin');
    }
}
