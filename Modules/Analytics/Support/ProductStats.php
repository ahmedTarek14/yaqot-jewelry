<?php

namespace Modules\Analytics\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Product\Models\Product;

/**
 * Records the anonymous per-product daily counters. One row per product per day, raised by a
 * single atomic upsert (never read-modify-write), so concurrent visits cannot lose a count.
 * Automated and prefetch requests are skipped. Nothing about the visitor is stored.
 */
class ProductStats
{
    public const TABLE = 'product_daily_stats';

    public function __construct(private readonly Request $request) {}

    public function recordView(Product $product): void
    {
        $this->record($product, 'views');
    }

    public function recordWhatsAppClick(Product $product): void
    {
        $this->record($product, 'whatsapp_clicks');
    }

    private function record(Product $product, string $column): void
    {
        if (! BotDetector::countsRequest($this->request)) {
            return;
        }

        DB::table(self::TABLE)->upsert(
            [[
                'product_id' => $product->getKey(),
                'date' => now()->toDateString(),
                'views' => $column === 'views' ? 1 : 0,
                'whatsapp_clicks' => $column === 'whatsapp_clicks' ? 1 : 0,
            ]],
            ['product_id', 'date'],
            // The unqualified column is the stored row's value in both MySQL and SQLite.
            [$column => DB::raw("$column + 1")],
        );
    }
}
