<?php

namespace Modules\Product\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Product\Models\Product;
use Modules\Product\Support\CatalogFilters;
use Modules\Product\Support\CatalogOptions;

class CatalogController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $taxonomies = CatalogOptions::taxonomies();
        $filters = CatalogFilters::fromRequest($request, $taxonomies);

        $query = Product::query()->visible()->forCard();
        $filters->apply($query);

        $products = $query->paginate(config('catalog.front.per_page'))->withQueryString();

        // A page past the end (an old link, a hand-edited URL) goes to the last page.
        if ($products->isEmpty() && $products->currentPage() > 1) {
            return redirect()->to($products->url($products->lastPage()));
        }

        return view('product::front.catalog', [
            'products' => $products,
            'filters' => $filters,
            'taxonomies' => $taxonomies,
            'attributes' => CatalogOptions::attributes(),
            'action' => route('front.catalog'),
        ]);
    }
}
