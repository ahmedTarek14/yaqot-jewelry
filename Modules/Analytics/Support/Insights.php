<?php

namespace Modules\Analytics\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Product\Models\Product;

/**
 * The dashboard's "Insights" card: top products for a 7 or 30 day range plus a 30-day daily
 * chart. Every query is an aggregate with a bounded result (the top lists load their ten
 * products with translations in one query), so the cost does not grow with the catalog.
 */
class Insights
{
    public const RANGES = [7, 30];

    public const DEFAULT_RANGE = 7;

    public const TOP = 10;

    public const CHART_DAYS = 30;

    public function __construct(private readonly StatsPruner $pruner) {}

    /**
     * The `?range=` query value: only 7 or 30 are accepted, anything else is the default.
     */
    public static function rangeFrom(Request $request): int
    {
        $range = $request->query('range');

        return is_string($range) && in_array((int) $range, self::RANGES, true) && (string) (int) $range === $range
            ? (int) $range
            : self::DEFAULT_RANGE;
    }

    /**
     * @return array<string, mixed>
     */
    public function forDashboard(int $range): array
    {
        $today = now()->startOfDay();
        $series = $this->daily($today);

        return [
            'range' => $range,
            'ranges' => self::RANGES,
            'mostViewed' => $this->top('views', $range, $today),
            'mostAsked' => $this->top('whatsapp_clicks', $range, $today),
            'chart' => LineChart::build($series),
            'days' => $series,
            'hasData' => array_sum(array_column($series, 'views')) + array_sum(array_column($series, 'whatsapp_clicks')) > 0,
            'retentionMonths' => $this->pruner->months(),
        ];
    }

    /**
     * The ten products with the highest total of a counter over the last $days days (today
     * included), ties broken by product id. Trashed products are left out.
     *
     * @return array<int, array{product: Product, count: int}>
     */
    public function top(string $column, int $days, Carbon $today): array
    {
        $rows = DB::table(ProductStats::TABLE)
            ->join('products', 'products.id', '=', ProductStats::TABLE.'.product_id')
            ->whereNull('products.deleted_at')
            ->whereBetween(ProductStats::TABLE.'.date', [$today->copy()->subDays($days - 1)->toDateString(), $today->toDateString()])
            ->groupBy(ProductStats::TABLE.'.product_id')
            ->havingRaw("sum({$column}) > 0")
            ->orderByRaw("sum({$column}) desc")
            ->orderBy(ProductStats::TABLE.'.product_id')
            ->limit(self::TOP)
            ->selectRaw(ProductStats::TABLE.".product_id, sum({$column}) as total")
            ->get();

        if ($rows->isEmpty()) {
            return [];
        }

        $products = Product::query()->whereKey($rows->pluck('product_id'))->with('translations')->get()->keyBy('id');

        return $rows->map(fn ($row) => ['product' => $products[$row->product_id], 'count' => (int) $row->total])->all();
    }

    /**
     * Daily totals over all products for the last CHART_DAYS days, oldest first, missing days as 0.
     *
     * @return array<int, array{date: string, views: int, whatsapp_clicks: int}>
     */
    public function daily(Carbon $today): array
    {
        $from = $today->copy()->subDays(self::CHART_DAYS - 1);

        $totals = DB::table(ProductStats::TABLE)
            ->whereBetween('date', [$from->toDateString(), $today->toDateString()])
            ->groupBy('date')
            ->selectRaw('date, sum(views) as views, sum(whatsapp_clicks) as whatsapp_clicks')
            ->get()
            ->keyBy(fn ($row) => substr((string) $row->date, 0, 10));

        $days = [];

        for ($day = $from->copy(); $day->lte($today); $day->addDay()) {
            $key = $day->toDateString();

            $days[] = [
                'date' => $key,
                'views' => (int) ($totals[$key]->views ?? 0),
                'whatsapp_clicks' => (int) ($totals[$key]->whatsapp_clicks ?? 0),
            ];
        }

        return $days;
    }
}
