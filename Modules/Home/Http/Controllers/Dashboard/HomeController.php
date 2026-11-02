<?php

namespace Modules\Home\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Modules\Analytics\Support\Insights;
use Modules\Category\Models\Category;
use Modules\Product\Models\Product;

class HomeController extends Controller
{
    /** How many recently updated products the dashboard lists. */
    private const RECENT_PRODUCTS = 8;

    public function index(Request $request, Insights $insights): View
    {
        // One aggregate query; toBase() keeps the SoftDeletes scope, so trashed products are excluded.
        $totals = Product::query()
            ->toBase()
            ->selectRaw('count(*) as total')
            ->selectRaw('sum(case when is_published = 1 then 1 else 0 end) as published')
            ->first();

        $total = (int) $totals->total;
        $published = (int) $totals->published;

        return view('home::dashboard.index', [
            'stats' => [
                'products' => $total,
                'published' => $published,
                'drafts' => $total - $published,
                'active_categories' => Category::active()->count(),
            ],
            'recentProducts' => Product::query()
                ->with(['translations', 'primaryImage'])
                ->orderByDesc('updated_at')
                ->orderByDesc('id')
                ->limit(self::RECENT_PRODUCTS)
                ->get(),
            'insights' => $insights->forDashboard(Insights::rangeFrom($request)),
        ]);
    }
}
