<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Modules\Auth\Models\Admin;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Admin users CRUD: list, search, sort, create, edit, toggle, delete.
 * The safeguards (self, last active admin) are in AdminSafeguardsTest.
 */
class AdminUsersTest extends TestCase
{
    use RefreshDatabase;

    private const STRONG = 'Str0ng-Passw0rd!';

    /** @return array<string, array{string}> */
    public static function locales(): array
    {
        return ['arabic' => ['ar'], 'english' => ['en']];
    }

    private function signIn(string $locale = 'en'): Admin
    {
        $this->refreshApplicationWithLocale($locale);
        $me = Admin::factory()->create(['name' => 'Me Myself', 'email' => 'me@example.com']);
        $this->actingAs($me, 'admin');

        return $me;
    }

    /**
     * Continue as a visitor (same app and database): a real request starts with a fresh guard.
     */
    private function asGuest(): void
    {
        $this->app['auth']->forgetGuards();
        $this->flushSession();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'New Admin',
            'email' => 'new.admin@example.com',
            'password' => self::STRONG,
            'password_confirmation' => self::STRONG,
        ], $overrides);
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->refreshApplicationWithLocale('en');
        $other = Admin::factory()->create();

        $this->get('/en/admin/admins')->assertRedirect('/en/admin/login');
        $this->get('/en/admin/admins/create')->assertRedirect('/en/admin/login');
        $this->post('/en/admin/admins', $this->payload())->assertRedirect('/en/admin/login');
        $this->get("/en/admin/admins/{$other->id}/edit")->assertRedirect('/en/admin/login');
        $this->put("/en/admin/admins/{$other->id}", [])->assertRedirect('/en/admin/login');
        $this->patch("/en/admin/admins/{$other->id}/active")->assertRedirect('/en/admin/login');
        $this->delete("/en/admin/admins/{$other->id}")->assertRedirect('/en/admin/login');
        $this->get('/en/admin/account')->assertRedirect('/en/admin/login');
        $this->put('/en/admin/account', [])->assertRedirect('/en/admin/login');

        $this->assertSame(1, Admin::count());
    }

    #[DataProvider('locales')]
    public function test_the_index_lists_admins_in_both_locales(string $locale): void
    {
        $me = $this->signIn($locale);
        $active = Admin::factory()->create(['name' => 'Active Person', 'last_login_at' => '2026-10-01 14:30:00']);
        $inactive = Admin::factory()->inactive()->create(['name' => 'Dormant Person']);

        $this->get("/$locale/admin/admins")
            ->assertOk()
            ->assertSee('<html lang="'.$locale.'"', false)
            ->assertSee(__('auth::admins.title', [], $locale))
            ->assertSee('Active Person')
            ->assertSee('Dormant Person')
            ->assertSee($active->email)
            ->assertSee(__('admin.status.active', [], $locale))
            ->assertSee(__('admin.status.inactive', [], $locale))
            ->assertSee(__('auth::admins.never', [], $locale))
            ->assertSee('data-toggle-url="'.route('admin.admins.toggle-active', $inactive).'"', false)
            ->assertSee('data-url="'.route('admin.admins.destroy', $active).'"', false)
            ->assertSee('href="'.route('admin.admins.edit', $active).'"', false)
            ->assertSee('datetime="2026-10-01T14:30:00+00:00"', false);

        // The signed-in admin is marked, links to My account, and has no switch or delete button.
        $this->get("/$locale/admin/admins")
            ->assertSee(__('auth::admins.you', [], $locale))
            ->assertSee('href="'.route('admin.account.edit').'"', false)
            ->assertDontSee('data-toggle-url="'.route('admin.admins.toggle-active', $me).'"', false)
            ->assertDontSee('data-url="'.route('admin.admins.destroy', $me).'"', false);
    }

    public function test_search_matches_name_and_email_and_shows_the_empty_state(): void
    {
        $this->signIn();
        Admin::factory()->create(['name' => 'Zainab Hassan', 'email' => 'zh@example.com']);
        Admin::factory()->create(['name' => 'Omar Ali', 'email' => 'omar.special@example.com']);

        $this->get('/en/admin/admins?q=Zainab')->assertSee('Zainab Hassan')->assertDontSee('Omar Ali');
        $this->get('/en/admin/admins?q=special@')->assertSee('Omar Ali')->assertDontSee('Zainab Hassan');
        $this->get('/en/admin/admins?q=nobody-matches')
            ->assertOk()
            ->assertSee(__('admin.listing.empty_filtered', [], 'en'));
        // LIKE wildcards are literal.
        $this->get('/en/admin/admins?q=%25')->assertDontSee('Zainab Hassan');
    }

    public function test_status_filter(): void
    {
        $this->signIn();
        Admin::factory()->inactive()->create(['name' => 'Dormant Person']);

        $this->get('/en/admin/admins?status=inactive')->assertSee('Dormant Person')->assertDontSee('Me Myself');
        $this->get('/en/admin/admins?status=active')->assertDontSee('Dormant Person')->assertSee('Me Myself');
    }

    public function test_sorting_uses_an_allowlist(): void
    {
        $this->signIn();
        Admin::factory()->create(['name' => 'Aaa First']);
        Admin::factory()->create(['name' => 'Zzz Last']);

        $this->get('/en/admin/admins?sort=name&dir=desc')->assertSeeInOrder(['Zzz Last', 'Me Myself', 'Aaa First']);
        $this->get('/en/admin/admins?sort=name&dir=asc')->assertSeeInOrder(['Aaa First', 'Me Myself', 'Zzz Last']);

        foreach (['email', 'is_active', 'last_login_at'] as $column) {
            $this->get("/en/admin/admins?sort=$column&dir=desc")->assertOk();
        }

        // Anything else falls back to the default sort instead of reaching the query.
        $this->get('/en/admin/admins?sort=password&dir=desc')->assertOk()->assertSeeInOrder(['Aaa First', 'Me Myself', 'Zzz Last']);
        $this->get('/en/admin/admins?sort[]=name')->assertOk();
    }

    public function test_the_index_is_paginated_and_a_page_past_the_end_redirects(): void
    {
        $this->signIn();
        Admin::factory()->count(25)->create();

        $this->get('/en/admin/admins')->assertOk()->assertSee('page=2', false);
        $this->get('/en/admin/admins?page=99')->assertRedirect('/en/admin/admins?page=2');
    }

    public function test_the_index_does_not_run_a_query_per_row(): void
    {
        $this->signIn();
        Admin::factory()->count(2)->create();

        DB::enableQueryLog();
        $this->get('/en/admin/admins')->assertOk();
        $few = count(DB::getQueryLog());

        Admin::factory()->count(15)->create();
        DB::flushQueryLog();
        $this->get('/en/admin/admins')->assertOk();
        $many = count(DB::getQueryLog());

        $this->assertSame($few, $many);
    }

    #[DataProvider('locales')]
    public function test_the_create_and_edit_forms_render_in_both_locales(string $locale): void
    {
        $this->signIn($locale);
        $other = Admin::factory()->create(['name' => 'Other Person']);

        $this->get("/$locale/admin/admins/create")
            ->assertOk()
            ->assertSee(__('auth::admins.create', [], $locale))
            ->assertSee('autocomplete="new-password"', false)
            ->assertSee(__('auth::admins.fields.password_policy', [], $locale))
            ->assertDontSee('name="is_active"', false);

        $this->get("/$locale/admin/admins/{$other->id}/edit")
            ->assertOk()
            ->assertSee(__('auth::admins.edit', [], $locale))
            ->assertSee('Other Person')
            ->assertSee('name="is_active"', false)
            ->assertSee(__('auth::admins.fields.new_password_help', [], $locale))
            ->assertDontSee($other->password);
    }

    public function test_editing_yourself_goes_to_my_account(): void
    {
        $me = $this->signIn();

        $this->get("/en/admin/admins/{$me->id}/edit")->assertRedirect('/en/admin/account');
    }

    public function test_an_admin_can_be_created(): void
    {
        $this->signIn();

        $this->postJson('/en/admin/admins', $this->payload(['name' => '  Spaced Name ', 'email' => '  New.Admin@Example.COM ']))
            ->assertOk()
            ->assertJson(['message' => __('admin.messages.saved', [], 'en'), 'redirect' => route('admin.admins.index')]);

        $admin = Admin::where('email', 'new.admin@example.com')->firstOrFail();
        $this->assertSame('Spaced Name', $admin->name);
        $this->assertTrue($admin->is_active);
        $this->assertNull($admin->last_login_at);
        $this->assertNotSame(self::STRONG, $admin->password);
        $this->assertTrue(Hash::check(self::STRONG, $admin->password));
    }

    public function test_a_new_admin_can_log_in(): void
    {
        $this->signIn();
        $this->postJson('/en/admin/admins', $this->payload())->assertOk();

        $this->asGuest();
        $this->post(route('admin.login.submit'), ['email' => 'new.admin@example.com', 'password' => self::STRONG])
            ->assertRedirect('/en/admin');
    }

    public function test_create_cannot_set_is_active_or_login_time_from_input(): void
    {
        $this->signIn();

        $this->postJson('/en/admin/admins', $this->payload(['is_active' => '0', 'last_login_at' => '2020-01-01']))->assertOk();

        $admin = Admin::where('email', 'new.admin@example.com')->firstOrFail();
        $this->assertTrue($admin->is_active);
        $this->assertNull($admin->last_login_at);
    }

    /** @return array<string, array{array<string, mixed>, string}> */
    public static function invalidCreate(): array
    {
        return [
            'missing name' => [['name' => ''], 'name'],
            'missing email' => [['email' => ''], 'email'],
            'bad email' => [['email' => 'not-an-email'], 'email'],
            'duplicate email' => [['email' => 'me@example.com'], 'email'],
            'duplicate email, other case' => [['email' => 'ME@Example.com'], 'email'],
            'missing password' => [['password' => '', 'password_confirmation' => ''], 'password'],
            'short password' => [['password' => 'Ab1!', 'password_confirmation' => 'Ab1!'], 'password'],
            'no symbol' => [['password' => 'Abcdefgh1234', 'password_confirmation' => 'Abcdefgh1234'], 'password'],
            'no number' => [['password' => 'Abcdefgh!!!!', 'password_confirmation' => 'Abcdefgh!!!!'], 'password'],
            'no upper case' => [['password' => 'abcdefgh12!!', 'password_confirmation' => 'abcdefgh12!!'], 'password'],
            'weak password' => [['password' => 'password', 'password_confirmation' => 'password'], 'password'],
            'confirmation mismatch' => [['password_confirmation' => 'Different-Passw0rd!'], 'password'],
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    #[DataProvider('invalidCreate')]
    public function test_create_validates_input(array $overrides, string $field): void
    {
        $this->signIn();

        $this->postJson('/en/admin/admins', $this->payload($overrides))
            ->assertUnprocessable()
            ->assertJsonValidationErrors($field);

        $this->assertSame(1, Admin::count());
    }

    public function test_create_validation_messages_are_translated(): void
    {
        $this->signIn('ar');

        $response = $this->postJson('/ar/admin/admins', $this->payload(['name' => '']))->assertUnprocessable();

        $this->assertStringContainsString(__('auth::admins.fields.name', [], 'ar'), $response->json('errors.name.0'));
    }

    public function test_an_admin_can_be_edited_without_touching_the_password(): void
    {
        $this->signIn();
        $other = Admin::factory()->create(['password' => self::STRONG]);
        $oldHash = $other->password;

        $this->putJson("/en/admin/admins/{$other->id}", [
            'name' => 'Renamed',
            'email' => 'Renamed@Example.com',
            'is_active' => '1',
            'password' => '',
            'password_confirmation' => '',
        ])->assertOk()->assertJson(['redirect' => route('admin.admins.index')]);

        $other->refresh();
        $this->assertSame('Renamed', $other->name);
        $this->assertSame('renamed@example.com', $other->email);
        $this->assertSame($oldHash, $other->password);
    }

    public function test_editing_keeps_the_own_email_valid_but_rejects_another_admins_email(): void
    {
        $this->signIn();
        $other = Admin::factory()->create(['email' => 'other@example.com']);

        $this->putJson("/en/admin/admins/{$other->id}", ['name' => 'X', 'email' => 'other@example.com', 'is_active' => '1'])->assertOk();
        $this->putJson("/en/admin/admins/{$other->id}", ['name' => 'X', 'email' => 'me@example.com', 'is_active' => '1'])
            ->assertUnprocessable()->assertJsonValidationErrors('email');
    }

    public function test_edit_validates_the_active_flag_and_the_new_password(): void
    {
        $this->signIn();
        $other = Admin::factory()->create();

        $this->putJson("/en/admin/admins/{$other->id}", ['name' => 'X', 'email' => 'x@example.com'])
            ->assertUnprocessable()->assertJsonValidationErrors('is_active');

        $this->putJson("/en/admin/admins/{$other->id}", [
            'name' => 'X', 'email' => 'x@example.com', 'is_active' => '1',
            'password' => 'weak', 'password_confirmation' => 'weak',
        ])->assertUnprocessable()->assertJsonValidationErrors('password');
    }

    public function test_an_admin_can_set_another_admins_new_password(): void
    {
        $this->signIn();
        $other = Admin::factory()->create(['email' => 'other@example.com', 'password' => 'Old-Passw0rd!!', 'remember_token' => 'old-token']);

        $this->putJson("/en/admin/admins/{$other->id}", [
            'name' => $other->name,
            'email' => $other->email,
            'is_active' => '1',
            'password' => self::STRONG,
            'password_confirmation' => self::STRONG,
        ])->assertOk();

        $other->refresh();
        $this->assertTrue(Hash::check(self::STRONG, $other->password));
        $this->assertNotSame('old-token', $other->remember_token);

        // The new password works for a real login, the old one no longer does.
        $this->asGuest();
        $this->post(route('admin.login.submit'), ['email' => 'other@example.com', 'password' => 'Old-Passw0rd!!'])
            ->assertSessionHasErrors('email');
        $this->assertGuest('admin');

        $this->post(route('admin.login.submit'), ['email' => 'other@example.com', 'password' => self::STRONG])
            ->assertRedirect('/en/admin');
        $this->assertAuthenticatedAs($other, 'admin');
    }

    public function test_the_token_is_not_rotated_when_the_password_is_unchanged(): void
    {
        $this->signIn();
        $other = Admin::factory()->create(['remember_token' => 'keep-me']);

        $this->putJson("/en/admin/admins/{$other->id}", ['name' => 'X', 'email' => 'x@example.com', 'is_active' => '1'])->assertOk();

        $this->assertSame('keep-me', $other->refresh()->remember_token);
    }

    public function test_an_admin_can_be_deactivated_and_activated_again(): void
    {
        $this->signIn();
        $other = Admin::factory()->create();

        $this->patchJson("/en/admin/admins/{$other->id}/active")
            ->assertOk()
            ->assertJson(['value' => false, 'label' => __('admin.status.inactive', [], 'en')]);
        $this->assertFalse($other->refresh()->is_active);

        $this->patchJson("/en/admin/admins/{$other->id}/active")
            ->assertOk()
            ->assertJson(['value' => true, 'label' => __('admin.status.active', [], 'en')]);
        $this->assertTrue($other->refresh()->is_active);
    }

    public function test_the_edit_form_can_deactivate_another_admin(): void
    {
        $this->signIn();
        $other = Admin::factory()->create();

        $this->putJson("/en/admin/admins/{$other->id}", ['name' => 'X', 'email' => 'x@example.com', 'is_active' => '0'])->assertOk();

        $this->assertFalse($other->refresh()->is_active);
    }

    public function test_another_admin_can_be_deleted_from_the_modal_form(): void
    {
        $this->signIn();
        $other = Admin::factory()->create();

        $this->from('/en/admin/admins')
            ->delete("/en/admin/admins/{$other->id}")
            ->assertRedirect('/en/admin/admins')
            ->assertSessionHas('success', __('admin.messages.deleted', [], 'en'));

        $this->assertModelMissing($other);
    }

    public function test_a_deleted_admin_cannot_be_reached_again(): void
    {
        $this->signIn();
        $other = Admin::factory()->create();
        $id = $other->id;
        $other->delete();

        $this->get("/en/admin/admins/$id/edit")->assertNotFound();
        $this->putJson("/en/admin/admins/$id", ['name' => 'X', 'email' => 'x@example.com', 'is_active' => '1'])->assertNotFound();
        $this->patchJson("/en/admin/admins/$id/active")->assertNotFound();
        $this->delete("/en/admin/admins/$id")->assertNotFound();
    }
}
