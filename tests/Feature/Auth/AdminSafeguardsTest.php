<?php

namespace Tests\Feature\Auth;

use Illuminate\Auth\SessionGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Models\Admin;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Nobody can lock the dashboard: not yourself, never the last active admin; and a
 * deactivated admin can neither log in nor keep an open session.
 */
class AdminSafeguardsTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, array{string}> */
    public static function locales(): array
    {
        return ['arabic' => ['ar'], 'english' => ['en']];
    }

    private function signIn(string $locale = 'en'): Admin
    {
        $this->refreshApplicationWithLocale($locale);
        $me = Admin::factory()->create();
        $this->actingAs($me, 'admin');

        return $me;
    }

    /**
     * @return array<string, mixed>
     */
    private function form(Admin $admin, bool $active): array
    {
        return ['name' => $admin->name, 'email' => $admin->email, 'is_active' => $active ? '1' : '0'];
    }

    /* ------------------------------------------------------------------ Self */

    #[DataProvider('locales')]
    public function test_you_cannot_deactivate_yourself_with_the_toggle(string $locale): void
    {
        $me = $this->signIn($locale);
        Admin::factory()->create();

        $this->patchJson("/$locale/admin/admins/{$me->id}/active")
            ->assertStatus(409)
            ->assertJson(['message' => __('auth::admins.safeguards.self_deactivate', [], $locale)]);

        $this->assertTrue($me->refresh()->is_active);
    }

    public function test_the_toggle_refusal_is_a_flash_for_a_non_json_request(): void
    {
        $me = $this->signIn();
        Admin::factory()->create();

        $this->from('/en/admin/admins')
            ->patch("/en/admin/admins/{$me->id}/active")
            ->assertRedirect('/en/admin/admins')
            ->assertSessionHas('error', __('auth::admins.safeguards.self_deactivate', [], 'en'));
    }

    #[DataProvider('locales')]
    public function test_you_cannot_deactivate_yourself_with_the_update_form(string $locale): void
    {
        $me = $this->signIn($locale);
        Admin::factory()->create();

        $this->putJson("/$locale/admin/admins/{$me->id}", $this->form($me, false))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['is_active' => __('auth::admins.safeguards.self_deactivate', [], $locale)]);

        $this->assertTrue($me->refresh()->is_active);
    }

    public function test_the_update_route_cannot_be_used_to_edit_yourself(): void
    {
        $me = $this->signIn();
        $hash = $me->password;
        Admin::factory()->create();

        // It would skip the current-password check of "My account".
        $this->putJson("/en/admin/admins/{$me->id}", [
            ...$this->form($me, true),
            'password' => 'Brand-New-Passw0rd!',
            'password_confirmation' => 'Brand-New-Passw0rd!',
        ])->assertForbidden();

        $this->assertSame($hash, $me->refresh()->password);
    }

    #[DataProvider('locales')]
    public function test_you_cannot_delete_yourself(string $locale): void
    {
        $me = $this->signIn($locale);
        Admin::factory()->create();

        $this->deleteJson("/$locale/admin/admins/{$me->id}")
            ->assertStatus(409)
            ->assertJson(['message' => __('auth::admins.safeguards.self_delete', [], $locale)]);

        $this->from("/$locale/admin/admins")
            ->delete("/$locale/admin/admins/{$me->id}")
            ->assertRedirect("/$locale/admin/admins")
            ->assertSessionHas('error', __('auth::admins.safeguards.self_delete', [], $locale));

        $this->assertModelExists($me);
    }

    /* ----------------------------------------------------------- Last active */

    /**
     * An admin who is not active any more (a session that was open when they were deactivated,
     * as actingAs ignores the provider) must still not be able to remove the last active admin.
     */
    private function lastActiveAdminAndAnInactiveActor(string $locale = 'en'): Admin
    {
        $this->refreshApplicationWithLocale($locale);
        $last = Admin::factory()->create();
        $this->actingAs(Admin::factory()->inactive()->create(), 'admin');

        return $last;
    }

    #[DataProvider('locales')]
    public function test_the_last_active_admin_cannot_be_deactivated_with_the_toggle(string $locale): void
    {
        $last = $this->lastActiveAdminAndAnInactiveActor($locale);

        $this->patchJson("/$locale/admin/admins/{$last->id}/active")
            ->assertStatus(409)
            ->assertJson(['message' => __('auth::admins.safeguards.last_deactivate', [], $locale)]);

        $this->assertTrue($last->refresh()->is_active);
    }

    #[DataProvider('locales')]
    public function test_the_last_active_admin_cannot_be_deactivated_with_the_update_form(string $locale): void
    {
        $last = $this->lastActiveAdminAndAnInactiveActor($locale);

        $this->putJson("/$locale/admin/admins/{$last->id}", $this->form($last, false))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['is_active' => __('auth::admins.safeguards.last_deactivate', [], $locale)]);

        $this->assertTrue($last->refresh()->is_active);
    }

    #[DataProvider('locales')]
    public function test_the_last_active_admin_cannot_be_deleted(string $locale): void
    {
        $last = $this->lastActiveAdminAndAnInactiveActor($locale);

        $this->deleteJson("/$locale/admin/admins/{$last->id}")
            ->assertStatus(409)
            ->assertJson(['message' => __('auth::admins.safeguards.last_delete', [], $locale)]);

        $this->from("/$locale/admin/admins")
            ->delete("/$locale/admin/admins/{$last->id}")
            ->assertRedirect("/$locale/admin/admins")
            ->assertSessionHas('error', __('auth::admins.safeguards.last_delete', [], $locale));

        $this->assertModelExists($last);
    }

    public function test_the_last_active_admin_can_still_be_edited_while_staying_active(): void
    {
        $last = $this->lastActiveAdminAndAnInactiveActor();

        $this->putJson("/en/admin/admins/{$last->id}", [...$this->form($last, true), 'name' => 'Renamed'])->assertOk();

        $this->assertSame('Renamed', $last->refresh()->name);
    }

    public function test_an_inactive_admin_can_be_deleted_and_activated_while_another_is_active(): void
    {
        $this->signIn();
        $dormant = Admin::factory()->inactive()->create();

        $this->patchJson("/en/admin/admins/{$dormant->id}/active")->assertOk();
        $this->assertTrue($dormant->refresh()->is_active);

        $dormant->forceFill(['is_active' => false])->save();
        $this->delete("/en/admin/admins/{$dormant->id}")->assertRedirect();
        $this->assertModelMissing($dormant);
    }

    public function test_with_two_active_admins_either_can_be_deactivated_but_not_both(): void
    {
        $me = $this->signIn();
        $other = Admin::factory()->create();

        $this->patchJson("/en/admin/admins/{$other->id}/active")->assertOk();

        // Now only $me is active: the other admin (acting through a stale session) cannot remove them.
        // A different browser: its own session, not the previous admin's.
        $this->flushSession();
        $this->actingAs($other->refresh(), 'admin');
        $this->patchJson("/en/admin/admins/{$me->id}/active")->assertStatus(409);
        $this->assertTrue($me->refresh()->is_active);
    }

    /* ------------------------------------------------------ Deactivated admin */

    public function test_a_deactivated_admin_cannot_log_in(): void
    {
        $this->refreshApplicationWithLocale('en');
        $admin = Admin::factory()->inactive()->create(['password' => 'Secret-Passw0rd!']);
        Admin::factory()->create();

        $this->post(route('admin.login.submit'), ['email' => $admin->email, 'password' => 'Secret-Passw0rd!'])
            ->assertSessionHasErrors(['email' => __('auth::admins.login.deactivated', [], 'en')]);

        $this->assertGuest('admin');
        $this->assertNull($admin->refresh()->last_login_at);
    }

    #[DataProvider('locales')]
    public function test_the_deactivated_message_is_translated(string $locale): void
    {
        $this->refreshApplicationWithLocale($locale);
        $admin = Admin::factory()->inactive()->create(['password' => 'Secret-Passw0rd!']);

        $this->post(route('admin.login.submit'), ['email' => $admin->email, 'password' => 'Secret-Passw0rd!'])
            ->assertSessionHasErrors(['email' => __('auth::admins.login.deactivated', [], $locale)]);
    }

    public function test_a_wrong_password_for_a_deactivated_admin_gets_the_generic_message(): void
    {
        $this->refreshApplicationWithLocale('en');
        $admin = Admin::factory()->inactive()->create(['password' => 'Secret-Passw0rd!']);

        $this->post(route('admin.login.submit'), ['email' => $admin->email, 'password' => 'wrong'])
            ->assertSessionHasErrors(['email' => __('auth.failed', [], 'en')]);
        $this->post(route('admin.login.submit'), ['email' => 'nobody@example.com', 'password' => 'Secret-Passw0rd!'])
            ->assertSessionHasErrors(['email' => __('auth.failed', [], 'en')]);
    }

    public function test_login_throttling_still_applies_to_a_deactivated_admin(): void
    {
        $this->refreshApplicationWithLocale('en');
        $admin = Admin::factory()->inactive()->create(['password' => 'Secret-Passw0rd!']);

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('admin.login.submit'), ['email' => $admin->email, 'password' => 'wrong']);
        }

        $this->post(route('admin.login.submit'), ['email' => $admin->email, 'password' => 'Secret-Passw0rd!'])
            ->assertSessionHasErrors('email');

        $this->assertStringContainsString('Too many login attempts', session('errors')->first('email'));
    }

    public function test_an_open_session_stops_working_after_the_admin_is_deactivated(): void
    {
        $this->refreshApplicationWithLocale('en');
        $admin = Admin::factory()->create(['password' => 'Secret-Passw0rd!']);

        $this->post(route('admin.login.submit'), ['email' => $admin->email, 'password' => 'Secret-Passw0rd!'])
            ->assertRedirect('/en/admin');
        $this->get('/en/admin/admins')->assertOk();

        // Another admin deactivates them (here: directly), and their next request is bounced.
        Admin::query()->whereKey($admin->id)->update(['is_active' => false]);
        // A real request starts with a fresh guard; the test client reuses the app.
        $this->app['auth']->forgetGuards();

        $this->get('/en/admin/admins')->assertRedirect('/en/admin/login');
        $this->get('/en/admin')->assertRedirect('/en/admin/login');
    }

    public function test_deactivating_through_the_toggle_ends_the_other_admins_session(): void
    {
        $this->signIn();
        $victim = Admin::factory()->create();

        $this->patchJson("/en/admin/admins/{$victim->id}/active")->assertOk();

        // The victim's browser holds a session with their id; the guard no longer resolves it.
        $this->app['auth']->forgetGuards();
        $this->withSession(['login_admin_'.sha1(SessionGuard::class) => $victim->id])
            ->get('/en/admin')->assertRedirect('/en/admin/login');
    }

    public function test_a_deactivated_admin_is_not_resolved_by_id_or_remember_token(): void
    {
        $admin = Admin::factory()->inactive()->create(['remember_token' => 'a-remember-token']);
        $provider = $this->app['auth']->createUserProvider('admins');

        $this->assertNull($provider->retrieveById($admin->id));
        $this->assertNull($provider->retrieveByToken($admin->id, 'a-remember-token'));

        $admin->forceFill(['is_active' => true])->save();

        $this->assertTrue($admin->is($provider->retrieveById($admin->id)));
        $this->assertTrue($admin->is($provider->retrieveByToken($admin->id, 'a-remember-token')));
    }

    /* ------------------------------------------------------------ Last login */

    public function test_last_login_is_recorded_on_a_successful_login(): void
    {
        $this->refreshApplicationWithLocale('en');
        $admin = Admin::factory()->create(['password' => 'Secret-Passw0rd!']);
        $this->assertNull($admin->last_login_at);

        $this->freezeTime();
        $this->post(route('admin.login.submit'), ['email' => $admin->email, 'password' => 'Secret-Passw0rd!'])
            ->assertRedirect('/en/admin');

        $this->assertSame(now()->toDateTimeString(), $admin->refresh()->last_login_at->toDateTimeString());
    }

    public function test_last_login_is_not_recorded_for_a_failed_login(): void
    {
        $this->refreshApplicationWithLocale('en');
        $admin = Admin::factory()->create(['password' => 'Secret-Passw0rd!']);

        $this->post(route('admin.login.submit'), ['email' => $admin->email, 'password' => 'wrong']);

        $this->assertNull($admin->refresh()->last_login_at);
    }

    public function test_the_intended_url_keeps_working_after_login(): void
    {
        $this->refreshApplicationWithLocale('en');
        $admin = Admin::factory()->create(['password' => 'Secret-Passw0rd!']);

        $this->get('/en/admin/admins')->assertRedirect('/en/admin/login');
        $this->post(route('admin.login.submit'), ['email' => $admin->email, 'password' => 'Secret-Passw0rd!'])
            ->assertRedirect('/en/admin/admins');
    }
}
