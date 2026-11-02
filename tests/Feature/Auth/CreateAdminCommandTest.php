<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Modules\Auth\Models\Admin;
use Tests\TestCase;

class CreateAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD_PROMPT = 'Password (min. 10 characters, upper and lower case, a number and a symbol)';

    public function test_it_rejects_a_password_that_is_long_enough_but_weak(): void
    {
        $this->artisan('yaqot:create-admin')
            ->expectsQuestion('Name', 'Someone')
            ->expectsQuestion('Email', 'someone@example.com')
            ->expectsQuestion(self::PASSWORD_PROMPT, 'alllowercaseletters')
            ->expectsQuestion('Confirm password', 'alllowercaseletters')
            ->assertFailed();

        $this->assertSame(0, Admin::count());
    }

    public function test_the_new_admin_is_active(): void
    {
        $this->artisan('yaqot:create-admin')
            ->expectsQuestion('Name', 'Store Owner')
            ->expectsQuestion('Email', 'owner@example.com')
            ->expectsQuestion(self::PASSWORD_PROMPT, 'Str0ng-Passw0rd!')
            ->expectsQuestion('Confirm password', 'Str0ng-Passw0rd!')
            ->assertSuccessful();

        $this->assertTrue(Admin::where('email', 'owner@example.com')->firstOrFail()->is_active);
    }

    public function test_it_creates_an_admin_with_a_hashed_password(): void
    {
        $this->artisan('yaqot:create-admin')
            ->expectsQuestion('Name', 'Store Owner')
            ->expectsQuestion('Email', 'Owner@Example.com')
            ->expectsQuestion(self::PASSWORD_PROMPT, 'Str0ng-Passw0rd!')
            ->expectsQuestion('Confirm password', 'Str0ng-Passw0rd!')
            ->expectsOutputToContain('created')
            ->assertSuccessful();

        $admin = Admin::where('email', 'owner@example.com')->first();

        $this->assertNotNull($admin);
        $this->assertSame('Store Owner', $admin->name);
        $this->assertNotSame('Str0ng-Passw0rd!', $admin->password);
        $this->assertTrue(Hash::check('Str0ng-Passw0rd!', $admin->password));
    }

    public function test_it_rejects_a_duplicate_email(): void
    {
        Admin::factory()->create(['email' => 'owner@example.com']);

        $this->artisan('yaqot:create-admin')
            ->expectsQuestion('Name', 'Someone')
            ->expectsQuestion('Email', 'owner@example.com')
            ->expectsQuestion(self::PASSWORD_PROMPT, 'Str0ng-Passw0rd!')
            ->expectsQuestion('Confirm password', 'Str0ng-Passw0rd!')
            ->assertFailed();

        $this->assertSame(1, Admin::count());
    }

    public function test_it_rejects_a_short_password(): void
    {
        $this->artisan('yaqot:create-admin')
            ->expectsQuestion('Name', 'Someone')
            ->expectsQuestion('Email', 'someone@example.com')
            ->expectsQuestion(self::PASSWORD_PROMPT, 'short')
            ->expectsQuestion('Confirm password', 'short')
            ->assertFailed();

        $this->assertSame(0, Admin::count());
    }

    public function test_it_rejects_a_password_confirmation_mismatch(): void
    {
        $this->artisan('yaqot:create-admin')
            ->expectsQuestion('Name', 'Someone')
            ->expectsQuestion('Email', 'someone@example.com')
            ->expectsQuestion(self::PASSWORD_PROMPT, 'Str0ng-Passw0rd!')
            ->expectsQuestion('Confirm password', 'another-password')
            ->assertFailed();

        $this->assertSame(0, Admin::count());
    }

    public function test_it_rejects_an_invalid_email(): void
    {
        $this->artisan('yaqot:create-admin')
            ->expectsQuestion('Name', 'Someone')
            ->expectsQuestion('Email', 'not-an-email')
            ->expectsQuestion(self::PASSWORD_PROMPT, 'Str0ng-Passw0rd!')
            ->expectsQuestion('Confirm password', 'Str0ng-Passw0rd!')
            ->assertFailed();

        $this->assertSame(0, Admin::count());
    }
}
