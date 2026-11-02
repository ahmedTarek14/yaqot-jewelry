<?php

namespace Modules\Analytics\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Modules\Analytics\Support\StatsPruner;

class StatsController extends Controller
{
    /**
     * Manual pruning: the host may have no cron for `yaqot:prune-stats`.
     */
    public function prune(StatsPruner $pruner): RedirectResponse
    {
        $deleted = $pruner->prune();

        return back()->with('success', __('analytics::analytics.insights.prune.done', [
            'count' => number_format($deleted),
        ]));
    }
}
