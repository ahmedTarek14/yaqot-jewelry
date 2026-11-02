<?php

namespace Tests\Feature\System;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Mcamara\LaravelLocalization\LaravelLocalization;
use Modules\Auth\Models\Admin;
use Modules\Home\Support\PendingMigrations;
use Tests\TestCase;

/**
 * The host has no shell: pending migrations are shown on the dashboard and applied by an admin
 * with one button (the equivalent of `php artisan migrate --force`).
 */
class SystemMigrateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (getenv(LaravelLocalization::ENV_ROUTE_KEY) === false) {
            $this->refreshApplicationWithLocale('en');
        }
    }

    private function withProbeMigration(): void
    {
        app('migrator')->path(__DIR__.'/migrations');
    }

    public function test_a_migrated_database_has_nothing_pending_and_shows_no_card(): void
    {
        $this->assertSame([], app(PendingMigrations::class)->names());

        $this->actingAs(Admin::factory()->create(), 'admin')
            ->get('/en/admin')
            ->assertOk()
            ->assertDontSee(route('admin.system.migrate'), false);
    }

    public function test_pending_migrations_are_listed_and_the_button_applies_them(): void
    {
        $this->withProbeMigration();
        $admin = Admin::factory()->create();

        $this->assertSame(['2099_01_01_000000_create_yq_probe_table'], app(PendingMigrations::class)->names());
        $this->assertFalse(Schema::hasTable('yq_probe'));

        $this->actingAs($admin, 'admin')
            ->get('/en/admin')
            ->assertOk()
            ->assertSee('Database updates')
            ->assertSee('action="'.route('admin.system.migrate').'"', false);

        $this->actingAs($admin, 'admin')
            ->post('/en/admin/system/migrate')
            ->assertRedirect(route('admin.dashboard'))
            ->assertSessionHas('success', '1 database update applied.');

        $this->assertTrue(Schema::hasTable('yq_probe'));
        $this->assertSame([], app(PendingMigrations::class)->names());

        // Pressing it again changes nothing.
        $this->actingAs($admin, 'admin')
            ->post('/en/admin/system/migrate')
            ->assertSessionHas('success', 'The database is already up to date.');
    }

    public function test_only_a_signed_in_admin_can_apply_migrations(): void
    {
        $this->withProbeMigration();

        $this->post('/en/admin/system/migrate')->assertRedirect(route('admin.login'));
        $this->assertFalse(Schema::hasTable('yq_probe'));
    }
}
