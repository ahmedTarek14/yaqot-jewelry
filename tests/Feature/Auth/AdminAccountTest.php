<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Modules\Auth\Models\Admin;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * "My account": own name, email and password. The current password is needed to set a
 * new password or to change the email.
 */
class AdminAccountTest extends TestCase
{
    use RefreshDatabase;

    private const CURRENT = 'Current-Passw0rd!';

    private const NEXT = 'Next-Passw0rd!!';

    /** @return array<string, array{string}> */
    public static function locales(): array
    {
        return ['arabic' => ['ar'], 'english' => ['en']];
    }

    private function signIn(string $locale = 'en'): Admin
    {
        $this->refreshApplicationWithLocale($locale);
        $me = Admin::factory()->create(['name' => 'Me Myself', 'email' => 'me@example.com', 'password' => self::CURRENT, 'remember_token' => 'old-token']);
        $this->actingAs($me, 'admin');

        return $me;
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Me Myself',
            'email' => 'me@example.com',
            'current_password' => '',
            'password' => '',
            'password_confirmation' => '',
        ], $overrides);
    }

    #[DataProvider('locales')]
    public function test_the_page_renders_in_both_locales(string $locale): void
    {
        $this->signIn($locale);

        $this->get("/$locale/admin/account")
            ->assertOk()
            ->assertSee('<html lang="'.$locale.'"', false)
            ->assertSee(__('auth::admins.account.title', [], $locale))
            ->assertSee('Me Myself')
            ->assertSee('value="me@example.com"', false)
            ->assertSee('autocomplete="current-password"', false)
            ->assertSee('autocomplete="new-password"', false)
            ->assertSee(__('auth::admins.fields.password_policy', [], $locale))
            ->assertSee('action="'.route('admin.account.update').'"', false);
    }

    public function test_the_navbar_links_to_my_account(): void
    {
        $this->signIn();

        $this->get('/en/admin/account')->assertSee('href="'.route('admin.account.edit').'"', false);
    }

    public function test_name_alone_can_change_without_the_current_password(): void
    {
        $me = $this->signIn();

        $this->putJson('/en/admin/account', $this->payload(['name' => 'New Name']))
            ->assertOk()
            ->assertJson(['message' => __('admin.messages.saved', [], 'en'), 'redirect' => route('admin.account.edit')]);

        $this->assertSame('New Name', $me->refresh()->name);
        $this->assertTrue(Hash::check(self::CURRENT, $me->password));
    }

    public function test_changing_the_email_requires_the_current_password(): void
    {
        $me = $this->signIn();

        $this->putJson('/en/admin/account', $this->payload(['email' => 'changed@example.com']))
            ->assertUnprocessable()->assertJsonValidationErrors('current_password');
        $this->putJson('/en/admin/account', $this->payload(['email' => 'changed@example.com', 'current_password' => 'wrong']))
            ->assertUnprocessable()->assertJsonValidationErrors('current_password');
        $this->assertSame('me@example.com', $me->refresh()->email);

        $this->putJson('/en/admin/account', $this->payload(['email' => ' Changed@Example.com ', 'current_password' => self::CURRENT]))->assertOk();
        $this->assertSame('changed@example.com', $me->refresh()->email);
    }

    public function test_the_email_must_stay_unique(): void
    {
        $this->signIn();
        Admin::factory()->create(['email' => 'taken@example.com']);

        $this->putJson('/en/admin/account', $this->payload(['email' => 'taken@example.com', 'current_password' => self::CURRENT]))
            ->assertUnprocessable()->assertJsonValidationErrors('email');
    }

    public function test_a_password_change_needs_the_current_password(): void
    {
        $me = $this->signIn();
        $new = ['password' => self::NEXT, 'password_confirmation' => self::NEXT];

        $this->putJson('/en/admin/account', $this->payload($new))
            ->assertUnprocessable()->assertJsonValidationErrors('current_password');
        $this->putJson('/en/admin/account', $this->payload([...$new, 'current_password' => 'not-my-password']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['current_password' => __('validation.current_password', [], 'en')]);

        $this->assertTrue(Hash::check(self::CURRENT, $me->refresh()->password));
    }

    public function test_the_wrong_current_password_message_is_arabic_in_the_arabic_admin(): void
    {
        $this->signIn('ar');

        $this->putJson('/ar/admin/account', $this->payload(['password' => self::NEXT, 'password_confirmation' => self::NEXT, 'current_password' => 'wrong']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['current_password' => __('validation.current_password', [], 'ar')]);
    }

    /** @return array<string, array{string, string}> */
    public static function weakPasswords(): array
    {
        return [
            'too short' => ['Ab1!', 'Ab1!'],
            'no symbol' => ['Abcdefgh1234', 'Abcdefgh1234'],
            'common' => ['password', 'password'],
            'mismatch' => ['Next-Passw0rd!!', 'Other-Passw0rd!!'],
        ];
    }

    #[DataProvider('weakPasswords')]
    public function test_a_weak_or_unconfirmed_new_password_is_rejected(string $password, string $confirmation): void
    {
        $me = $this->signIn();

        $this->putJson('/en/admin/account', $this->payload([
            'current_password' => self::CURRENT, 'password' => $password, 'password_confirmation' => $confirmation,
        ]))->assertUnprocessable()->assertJsonValidationErrors('password');

        $this->assertTrue(Hash::check(self::CURRENT, $me->refresh()->password));
    }

    public function test_the_password_can_be_changed_and_the_session_stays_valid(): void
    {
        $me = $this->signIn();

        $this->putJson('/en/admin/account', $this->payload([
            'current_password' => self::CURRENT, 'password' => self::NEXT, 'password_confirmation' => self::NEXT,
        ]))->assertOk();

        $me->refresh();
        $this->assertTrue(Hash::check(self::NEXT, $me->password));
        $this->assertNotSame('old-token', $me->remember_token);

        // Still signed in on this browser.
        $this->get('/en/admin/account')->assertOk();
        $this->assertAuthenticatedAs($me, 'admin');
    }

    public function test_the_new_password_works_for_login_and_the_old_one_does_not(): void
    {
        $this->signIn();
        $this->putJson('/en/admin/account', $this->payload([
            'current_password' => self::CURRENT, 'password' => self::NEXT, 'password_confirmation' => self::NEXT,
        ]))->assertOk();

        $this->app['auth']->forgetGuards();
        $this->flushSession();
        $this->post(route('admin.login.submit'), ['email' => 'me@example.com', 'password' => self::CURRENT])->assertSessionHasErrors('email');
        $this->post(route('admin.login.submit'), ['email' => 'me@example.com', 'password' => self::NEXT])->assertRedirect('/en/admin');
    }

    public function test_name_is_required(): void
    {
        $this->signIn();

        $this->putJson('/en/admin/account', $this->payload(['name' => '']))
            ->assertUnprocessable()->assertJsonValidationErrors('name');
    }

    public function test_an_account_update_cannot_change_activity_or_login_time(): void
    {
        $me = $this->signIn();

        $this->putJson('/en/admin/account', $this->payload(['is_active' => '0', 'last_login_at' => '2020-01-01']))->assertOk();

        $me->refresh();
        $this->assertTrue($me->is_active);
        $this->assertNull($me->last_login_at);
    }
}
