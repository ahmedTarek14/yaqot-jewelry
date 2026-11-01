<?php

namespace Modules\Category\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Category\Models\Category;
use Modules\Product\Support\TaxonomyPage;

class CategoryController extends Controller
{
    public function show(Request $request, string $slug): View|RedirectResponse
    {
        $category = Category::query()->onSite()->with('translations')->where('slug', $slug)->firstOrFail();

        return TaxonomyPage::show($request, 'category', $category);
    }
}
