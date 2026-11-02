<?php

namespace Modules\Product\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Support\PlainText;
use App\Support\WhatsApp;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\View\View;
use Modules\Product\Models\Product;

class ProductController extends Controller
{
    public function show(string $slug, WhatsApp $whatsapp): View
    {
        // Drafts, trashed products, products without an image or a name in either
        // language, and unknown slugs are all the same 404.
        $product = Product::query()
            ->visible()
            ->where('slug', $slug)
            ->with([
                'translations',
                'images',
                'brand.translations',
                'goldColor.translations',
                'stoneType.translations',
                'categories' => fn ($query) => $query->active()->ordered()->with('translations'),
                'collections' => fn ($query) => $query->active()->ordered()->with('translations'),
            ])
            ->firstOrFail();

        $pageUrl = route('front.products.show', $product->slug);
        $brand = $product->brand?->is_active ? $product->brand : null;
        $jewelry = $product->isJewelry();

        return view('product::front.show', [
            'product' => $product,
            'brand' => $brand,
            // A brand's page lists jewelry only, so a bar's or coin's brand links to the bullion
            // page filtered by that brand (the maker).
            'brandUrl' => $brand ? ($jewelry
                ? route('front.brands.show', $brand->slug)
                : route('front.bullion', ['brand' => $brand->slug])) : null,
            'title' => $brand ? "{$product->name} | {$brand->name}" : $product->name,
            // Bars and coins belong to the bullion page: no category crumb, and their categories
            // and collections (whose pages list jewelry only) are not shown.
            'listLabel' => $jewelry ? __('product::catalog.title') : __('product::bullion.title'),
            'listUrl' => $jewelry ? route('front.catalog') : route('front.bullion'),
            'categories' => $jewelry ? $product->categories : $product->newCollection(),
            'collections' => $jewelry ? $product->collections : $product->newCollection(),
            'categoryCrumb' => $jewelry ? $product->categories->first() : null,
            'specs' => $this->specs($product),
            'paragraphs' => PlainText::paragraphs($product->description),
            'whatsappUrl' => $whatsapp->productUrl($product->name, $product->sku, $pageUrl),
            'contactUrl' => route('front.contact'),
            'related' => $this->related($product),
        ]);
    }

    /**
     * Label => value pairs for the attributes list; empty ones are left out.
     *
     * @return array<int, array{label: string, value: string}>
     */
    private function specs(Product $product): array
    {
        $jewelry = $product->isJewelry();

        // A deactivated option is hidden from the public site, like a deactivated brand. Bars and
        // coins show their type instead of a gold colour and stones.
        $values = [
            'type' => $jewelry ? null : __("product::product.types.{$product->type}"),
            'karat' => $product->karat !== null ? __("product::product.karats.{$product->karat}") : null,
            'gold_color' => $jewelry && $product->goldColor?->is_active ? $product->goldColor->name : null,
            'stone_type' => $jewelry && $product->stoneType?->is_active ? $product->stoneType->name : null,
            'weight' => $product->weight_label,
        ];

        $specs = [];

        foreach ($values as $key => $value) {
            if ($value !== null && $value !== '') {
                $specs[] = ['label' => __("product::show.attributes.$key"), 'value' => $value];
            }
        }

        return $specs;
    }

    /**
     * Jewelry: visible pieces of jewelry that share an active category with this one.
     * Bars and coins: the other visible bars and coins. Newest first either way.
     *
     * @return Collection<int, Product>
     */
    private function related(Product $product): Collection
    {
        if (! $product->isJewelry()) {
            return Product::query()
                ->visible()
                ->bullion()
                ->forCard()
                ->whereKeyNot($product->getKey())
                ->latestPublished()
                ->limit((int) config('catalog.front.related'))
                ->get();
        }

        $categoryIds = $product->categories->modelKeys();

        if ($categoryIds === []) {
            return $product->newCollection();
        }

        return Product::query()
            ->visible()
            ->jewelry()
            ->forCard()
            ->whereKeyNot($product->getKey())
            ->whereHas('categories', fn ($query) => $query->whereIn('categories.id', $categoryIds))
            ->latestPublished()
            ->limit((int) config('catalog.front.related'))
            ->get();
    }
}
