<?php

namespace Tests\Feature\Analytics;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Analytics\Models\ProductDailyStat;
use Modules\Auth\Models\Admin;
use Modules\Product\Models\Product;
use Tests\TestCase;

/**
 * Counters older than 13 months are removed by `yaqot:prune-stats` (cron) or by the dashboard
 * button (no cron needed). The cutoff day itself (exactly 13 months ago) is kept.
 */
class PruneStatsTest extends TestCase
{
    use RefreshDatabase;

    /** Today is 2026-11-02, so the oldest kept day is 2025-10-02. */
    private function seedRows(): void
    {
        $this->travelTo('2026-11-02 12:00:00');
        $product = Product::factory()->create();

        foreach (['2025-09-01', '2025-10-01', '2025-10-02', '2025-10-03', '2026-11-02'] as $date) {
            ProductDailyStat::factory()->for($product)->on($date)->create();
        }
    }

    /** @return array<int, string> */
    private function remainingDates(): array
    {
        return ProductDailyStat::query()->orderBy('date')->get()->map(fn ($row) => $row->date->toDateString())->all();
    }

    public function test_the_command_deletes_only_rows_older_than_thirteen_months(): void
    {
        $this->seedRows();

        $this->artisan('yaqot:prune-stats')
            ->expectsOutputToContain('Deleted 2 counter row(s) older than 13 month(s).')
            ->assertSuccessful();

        $this->assertSame(['2025-10-02', '2025-10-03', '2026-11-02'], $this->remainingDates());
    }

    public function test_the_command_accepts_a_months_option(): void
    {
        $this->seedRows();

        $this->artisan('yaqot:prune-stats --months=1')->expectsOutputToContain('Deleted 4 counter row(s)')->assertSuccessful();

        $this->assertSame(['2026-11-02'], $this->remainingDates());
    }

    public function test_the_command_rejects_a_bad_months_option_and_deletes_nothing(): void
    {
        $this->seedRows();

        $this->artisan('yaqot:prune-stats --months=abc')->assertExitCode(2);
        $this->artisan('yaqot:prune-stats --months=0')->assertExitCode(2);

        $this->assertSame(5, ProductDailyStat::count());
    }

    public function test_the_command_is_scheduled_daily(): void
    {
        $events = collect(app(Schedule::class)->events())
            ->filter(fn ($event) => str_contains($event->command, 'yaqot:prune-stats'));

        $this->assertCount(1, $events);
    }

    public function test_the_dashboard_button_prunes_and_reports_how_many(): void
    {
        $this->refreshApplicationWithLocale('en');
        $this->seedRows();
        $this->actingAs(Admin::factory()->create(), 'admin');

        $this->from('/en/admin')
            ->post('/en/admin/analytics/prune')
            ->assertRedirect('/en/admin')
            ->assertSessionHas('success', __('analytics::analytics.insights.prune.done', ['count' => '2']));

        $this->assertSame(['2025-10-02', '2025-10-03', '2026-11-02'], $this->remainingDates());
    }

    public function test_the_dashboard_has_the_button_inside_a_confirmation_modal(): void
    {
        $this->refreshApplicationWithLocale('en');
        $this->actingAs(Admin::factory()->create(), 'admin');

        $this->get('/en/admin')
            ->assertOk()
            ->assertSee('data-bs-target="#prune-modal"', false)
            ->assertSee('id="prune-modal"', false)
            ->assertSee('action="'.route('admin.analytics.prune').'"', false)
            ->assertSee(__('analytics::analytics.insights.retention', ['months' => 13]));
    }

    public function test_guests_cannot_prune(): void
    {
        $this->refreshApplicationWithLocale('en');
        $this->seedRows();

        $this->post('/en/admin/analytics/prune')->assertRedirect('http://localhost/en/admin/login');

        $this->assertSame(5, ProductDailyStat::count());
    }
}
