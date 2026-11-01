<?php

namespace Modules\Brand\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Brand\Models\Brand;
use Modules\Product\Support\TaxonomyPage;

class BrandController extends Controller
{
    public function show(Request $request, string $slug): View|RedirectResponse
    {
        $brand = Brand::query()->onSite()->with('translations')->where('slug', $slug)->firstOrFail();

        return TaxonomyPage::show($request, 'brand', $brand);
    }
}
