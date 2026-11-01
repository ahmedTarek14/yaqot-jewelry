<?php

namespace Modules\Collection\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Collection\Models\Collection;
use Modules\Product\Support\TaxonomyPage;

class CollectionController extends Controller
{
    public function show(Request $request, string $slug): View|RedirectResponse
    {
        $collection = Collection::query()->onSite()->with('translations')->where('slug', $slug)->firstOrFail();

        return TaxonomyPage::show($request, 'collection', $collection);
    }
}
