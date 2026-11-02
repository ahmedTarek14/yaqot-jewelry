<?php

namespace Modules\Product\Support;

use App\Support\PlainText;
use App\Support\SeoMeta;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\View\View;
use Modules\Product\Models\Product;

/**
 * A category, collection or brand page: a header for the record, then the catalog's filters,
 * sort, results and pagination, scoped to the record's visible products. The page's own filter
 * is left out of the form, so a stray ?category=… on the category page is ignored.
 * Used by the three Front controllers, which only find the record (404 unless onSite()).
 */
final class TaxonomyPage
{
    /** Filter name => the route that shows the page. */
    private const ROUTES = [
        'category' => 'front.categories.show',
        'collection' => 'front.collections.show',
        'brand' => 'front.brands.show',
    ];

    /**
     * @param  'category'|'collection'|'brand'  $type
     */
    public static function show(Request $request, string $type, Model $record): View|RedirectResponse
    {
        $taxonomies = CatalogOptions::taxonomies([$type]);
        $attributes = CatalogOptions::attributes();
        $filters = CatalogFilters::fromRequest($request, $taxonomies, $attributes);

        $query = Product::query()->visible()->forCard();
        match ($type) {
            'brand' => $query->where($query->qualifyColumn('brand_id'), $record->getKey()),
            'category' => $query->whereHas('categories', fn (Builder $q) => $q->whereKey($record->getKey())),
            'collection' => $query->whereHas('collections', fn (Builder $q) => $q->whereKey($record->getKey())),
        };
        $filters->apply($query);

        // The links carry the visitor's other filters, but not a stray ?category=… on the category
        // page: that filter is ignored here, so it must not follow the visitor from page to page.
        $products = $query->paginate(config('catalog.front.per_page'))
            ->appends(Arr::except($request->query(), [$type]));

        // A page past the end (an old link, a hand-edited URL) goes to the last page.
        if ($products->isEmpty() && $products->currentPage() > 1) {
            return redirect()->to($products->url($products->lastPage()));
        }

        // Brands have neither a description nor an image.
        $hasDescription = in_array('description', $record->translatedAttributes ?? [], true);

        $description = $hasDescription ? PlainText::paragraphs($record->getAttribute('description')) : [];
        $image = method_exists($record, 'publicImageUrl') ? $record->publicImageUrl() : null;

        return view('product::front.taxonomy', [
            'name' => $record->getAttribute('name'),
            'description' => $description,
            'image' => $image,
            'metaDescription' => SeoMeta::describe(implode(' ', $description)),
            'metaImage' => SeoMeta::absolute($image),
            'metaImageAlt' => $record->getAttribute('name'),
            'products' => $products,
            'filters' => $filters,
            'taxonomies' => $taxonomies,
            'attributes' => $attributes,
            'action' => route(self::ROUTES[$type], $record->getAttribute('slug')),
        ]);
    }
}
