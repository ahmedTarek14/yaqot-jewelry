<?php

namespace Tests\Feature\Analytics;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Analytics\Models\ProductDailyStat;
use Modules\Analytics\Support\Insights;
use Modules\Auth\Models\Admin;
use Modules\Product\Models\Product;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The dashboard "Insights" card: top products for 7 or 30 days and the 30-day SVG chart.
 */
class InsightsDashboardTest extends TestCase
{
    use RefreshDatabase;

    private const TODAY = '2026-11-10';

    /** @return array<string, array{string}> */
    public static function locales(): array
    {
        return ['arabic' => ['ar'], 'english' => ['en']];
    }

    private function signIn(string $locale = 'en'): static
    {
        $this->refreshApplicationWithLocale($locale);
        $this->travelTo(self::TODAY.' 12:00:00');

        return $this->actingAs(Admin::factory()->create(), 'admin');
    }

    private function product(?string $name = null): Product
    {
        return Product::factory()->create($name === null ? [] : ['en' => ['name' => $name], 'ar' => ['name' => "ع $name"]]);
    }

    private function stat(Product $product, string $date, int $views, int $clicks = 0): void
    {
        ProductDailyStat::factory()->for($product)->on($date)->counts($views, $clicks)->create();
    }

    public function test_the_seven_and_thirty_day_ranges_have_exact_boundaries(): void
    {
        $this->signIn();
        $product = $this->product();
        $this->stat($product, '2026-11-10', 1);   // today
        $this->stat($product, '2026-11-04', 10);  // 7th day back: in the 7-day range
        $this->stat($product, '2026-11-03', 100); // 8th day back: only in the 30-day range
        $this->stat($product, '2026-10-12', 1000); // 30th day back: in the 30-day range
        $this->stat($product, '2026-10-11', 10000); // 31st day back: in neither

        $insights = app(Insights::class);
        $today = Carbon::parse(self::TODAY);

        $this->assertSame(11, $insights->top('views', 7, $today)[0]['count']);
        $this->assertSame(1111, $insights->top('views', 30, $today)[0]['count']);
    }

    public function test_the_page_shows_the_chosen_range_and_ignores_unknown_ones(): void
    {
        $this->signIn();
        $recent = $this->product('Recent Ring');
        $older = $this->product('Older Ring');
        $this->stat($recent, '2026-11-09', 5);
        $this->stat($older, '2026-10-20', 50);

        // The "recently updated" table further down lists every product, so look at the card only.
        $card = function (string $query): string {
            $html = $this->get('/en/admin'.$query)->assertOk()->getContent();
            preg_match('#<section class="card mt-4 yq-insights".*?</section>#s', $html, $match);

            return $match[0];
        };

        $this->assertStringContainsString('Recent Ring', $card(''));
        $this->assertStringNotContainsString('Older Ring', $card(''));
        $this->assertStringNotContainsString('Older Ring', $card('?range=7'));
        $this->assertStringContainsString('Older Ring', $card('?range=30'));
        $this->assertStringContainsString('Recent Ring', $card('?range=30'));

        foreach (['90', '-1', 'abc', '7.5', '030', '', '[]=30'] as $bad) {
            $query = $bad === '[]=30' ? '?range[]=30' : '?range='.$bad;
            $this->assertStringNotContainsString('Older Ring', $card($query), $query);
        }

        $this->assertStringContainsString('aria-current="true"', $card('?range=30'));
    }

    public function test_top_lists_are_limited_to_ten_ordered_by_count_then_product_id(): void
    {
        $this->signIn();
        $products = collect(range(1, 12))->map(fn () => $this->product());
        // The first three tie on 5; the rest have distinct counts; the last two are the lowest.
        foreach ($products as $index => $product) {
            $this->stat($product, self::TODAY, match (true) {
                $index < 3 => 5,
                $index >= 10 => 1,
                default => 100 - $index,
            });
        }

        $top = app(Insights::class)->top('views', 7, Carbon::parse(self::TODAY));

        $this->assertCount(10, $top);
        $ids = array_map(fn ($row) => $row['product']->id, $top);
        // Highest counts first (100-3 .. 100-9 = 97..91), then the three that tie on 5 by id.
        $this->assertSame(
            [...$products->slice(3, 7)->pluck('id')->all(), ...$products->slice(0, 3)->pluck('id')->all()],
            $ids,
        );
        $this->assertSame([97, 96, 95, 94, 93, 92, 91, 5, 5, 5], array_map(fn ($row) => $row['count'], $top));
    }

    public function test_whatsapp_clicks_have_their_own_ranking(): void
    {
        $this->signIn();
        $quiet = $this->product('Quiet Piece');
        $asked = $this->product('Asked Piece');
        $this->stat($quiet, self::TODAY, 500, 0);
        $this->stat($asked, self::TODAY, 3, 9);

        $insights = app(Insights::class);
        $today = Carbon::parse(self::TODAY);

        $this->assertSame([$asked->id], array_map(fn ($row) => $row['product']->id, $insights->top('whatsapp_clicks', 7, $today)));
        $this->assertSame([$quiet->id, $asked->id], array_map(fn ($row) => $row['product']->id, $insights->top('views', 7, $today)));
    }

    public function test_trashed_products_are_left_out(): void
    {
        $this->signIn();
        $kept = $this->product('Kept Piece');
        $gone = $this->product('Gone Piece');
        $this->stat($kept, self::TODAY, 1, 1);
        $this->stat($gone, self::TODAY, 99, 99);
        $gone->delete();

        $this->get('/en/admin')->assertOk()->assertSee('Kept Piece')->assertDontSee('Gone Piece');
        $this->assertCount(1, app(Insights::class)->top('views', 7, Carbon::parse(self::TODAY)));
    }

    #[DataProvider('locales')]
    public function test_the_empty_state_and_an_empty_chart(string $locale): void
    {
        $this->signIn($locale);

        $html = $this->get("/$locale/admin")->assertOk()
            ->assertSee(__('analytics::analytics.insights.title', [], $locale))
            ->getContent();

        $this->assertSame(2, substr_count($html, e(__('analytics::analytics.insights.empty', [], $locale))));
        $this->assertStringNotContainsString('yq-insights__list', $html);
        $this->assertStringContainsString('class="yq-chart"', $html);
    }

    #[DataProvider('locales')]
    public function test_the_chart_has_thirty_zero_filled_points_and_a_text_alternative(string $locale): void
    {
        $this->signIn($locale);
        $product = $this->product();
        $this->stat($product, '2026-11-10', 8, 0);
        $this->stat($product, '2026-10-15', 2, 0);

        $html = $this->get("/$locale/admin")->assertOk()->getContent();

        preg_match_all('/<polyline class="yq-chart__line yq-chart__line--(views|clicks)" points="([^"]+)"/', $html, $matches, PREG_SET_ORDER);
        $this->assertCount(2, $matches);

        foreach ($matches as [, $series, $points]) {
            $pairs = explode(' ', $points);
            $this->assertCount(30, $pairs, $series);
        }

        // No clicks at all: every point of the clicks series sits on the baseline (y = 210).
        $clicks = collect($matches)->firstWhere(1, 'clicks')[2];
        $this->assertSame(30, substr_count($clicks, ',210'));
        // Views: 28 zero days on the baseline, 2 non-zero.
        $views = collect($matches)->firstWhere(1, 'views')[2];
        $this->assertSame(28, substr_count($views, ',210'));

        // Accessible: titled svg, direction-safe wrapper, and the numbers as a 30-row table.
        $this->assertStringContainsString('role="img"', $html);
        $this->assertStringContainsString('<title id="insights-chart-title">'.e(__('analytics::analytics.insights.chart_title', [], $locale)).'</title>', $html);
        $this->assertStringContainsString('class="yq-chart" dir="ltr"', $html);
        $this->assertStringContainsString('<details class="yq-chart__data">', $html);
        $this->assertSame(30, substr_count($html, '<th scope="row" class="text-sm fw-normal">'));
    }

    public function test_the_chart_sums_every_product_per_day(): void
    {
        $this->signIn();
        $one = $this->product();
        $two = $this->product();
        $this->stat($one, '2026-11-09', 3, 1);
        $this->stat($two, '2026-11-09', 4, 2);

        $days = collect(app(Insights::class)->daily(Carbon::parse(self::TODAY)));

        $this->assertCount(30, $days);
        $this->assertSame('2026-10-12', $days->first()['date']);
        $this->assertSame(self::TODAY, $days->last()['date']);
        $this->assertSame(['date' => '2026-11-09', 'views' => 7, 'whatsapp_clicks' => 3], $days->firstWhere('date', '2026-11-09'));
        $this->assertSame(0, $days->firstWhere('date', '2026-11-08')['views']);
    }

    public function test_the_dashboard_stays_cheap_as_products_grow(): void
    {
        $this->signIn();
        foreach (range(1, 2) as $i) {
            $this->stat($this->product(), self::TODAY, $i, $i);
        }
        $this->get('/en/admin')->assertOk(); // warm caches

        $few = $this->countQueries(fn () => $this->get('/en/admin')->assertOk());

        foreach (range(1, 25) as $i) {
            $product = $this->product();
            $this->stat($product, self::TODAY, $i, $i);
            $this->stat($product, '2026-11-08', $i, $i);
        }
        $many = $this->countQueries(fn () => $this->get('/en/admin')->assertOk());

        $this->assertSame($few, $many);
    }

    private function countQueries(callable $callback): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $callback();
        DB::disableQueryLog();

        return count(DB::getQueryLog());
    }
}
