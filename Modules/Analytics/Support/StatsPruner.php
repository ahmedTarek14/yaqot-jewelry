<?php

namespace Modules\Analytics\Support;

use Modules\Analytics\Models\ProductDailyStat;

/**
 * Removes counters older than the retention period. Shared by the artisan command and the
 * dashboard button (a host may have no cron).
 */
class StatsPruner
{
    public function months(): int
    {
        return max(1, (int) config('analytics.retention_months', 13));
    }

    /**
     * The oldest day that is kept: rows dated strictly before it are deleted.
     */
    public function cutoff(?int $months = null): string
    {
        return now()->subMonthsNoOverflow($months ?? $this->months())->toDateString();
    }

    /**
     * @return int the number of rows deleted
     */
    public function prune(?int $months = null): int
    {
        return ProductDailyStat::query()->where('date', '<', $this->cutoff($months))->delete();
    }
}
