<?php

namespace Modules\Analytics\Console;

use Illuminate\Console\Command;
use Modules\Analytics\Support\StatsPruner;

class PruneStatsCommand extends Command
{
    protected $signature = 'yaqot:prune-stats {--months= : Keep this many months (default: analytics.retention_months, 13)}';

    protected $description = 'Delete daily product counters older than the retention period';

    public function handle(StatsPruner $pruner): int
    {
        $option = $this->option('months');

        if ($option !== null && (! ctype_digit((string) $option) || (int) $option < 1)) {
            $this->error('--months must be a whole number of at least 1.');

            return self::INVALID;
        }

        $months = $option === null ? $pruner->months() : (int) $option;
        $deleted = $pruner->prune($months);

        $this->info("Deleted $deleted counter row(s) older than $months month(s).");

        return self::SUCCESS;
    }
}
