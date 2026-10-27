<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Modules\Auth\Models\Admin;
use Tests\TestCase;

class CreateAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_an_admin_with_a_hashed_password(): void
    {
        $this->artisan('yaqot:create-admin')
            ->expectsQuestion('Name', 'Store Owner')
            ->expectsQuestion('Email', 'Owner@Example.com')
            ->expectsQuestion('Password (min. 8 characters)', 'a-strong-password')
            ->expectsQuestion('Confirm password', 'a-strong-password')
            ->expectsOutputToContain('created')
            ->assertSuccessful();

        $admin = Admin::where('email', 'owner@example.com')->first();

        $this->assertNotNull($admin);
        $this->assertSame('Store Owner', $admin->name);
        $this->assertNotSame('a-strong-password', $admin->password);
        $this->assertTrue(Hash::check('a-strong-password', $admin->password));
    }

    public function test_it_rejects_a_duplicate_email(): void
    {
        Admin::factory()->create(['email' => 'owner@example.com']);

        $this->artisan('yaqot:create-admin')
            ->expectsQuestion('Name', 'Someone')
            ->expectsQuestion('Email', 'owner@example.com')
            ->expectsQuestion('Password (min. 8 characters)', 'a-strong-password')
            ->expectsQuestion('Confirm password', 'a-strong-password')
            ->assertFailed();

        $this->assertSame(1, Admin::count());
    }

    public function test_it_rejects_a_short_password(): void
    {
        $this->artisan('yaqot:create-admin')
            ->expectsQuestion('Name', 'Someone')
            ->expectsQuestion('Email', 'someone@example.com')
            ->expectsQuestion('Password (min. 8 characters)', 'short')
            ->expectsQuestion('Confirm password', 'short')
            ->assertFailed();

        $this->assertSame(0, Admin::count());
    }

    public function test_it_rejects_a_password_confirmation_mismatch(): void
    {
        $this->artisan('yaqot:create-admin')
            ->expectsQuestion('Name', 'Someone')
            ->expectsQuestion('Email', 'someone@example.com')
            ->expectsQuestion('Password (min. 8 characters)', 'a-strong-password')
            ->expectsQuestion('Confirm password', 'another-password')
            ->assertFailed();

        $this->assertSame(0, Admin::count());
    }

    public function test_it_rejects_an_invalid_email(): void
    {
        $this->artisan('yaqot:create-admin')
            ->expectsQuestion('Name', 'Someone')
            ->expectsQuestion('Email', 'not-an-email')
            ->expectsQuestion('Password (min. 8 characters)', 'a-strong-password')
            ->expectsQuestion('Confirm password', 'a-strong-password')
            ->assertFailed();

        $this->assertSame(0, Admin::count());
    }
}
