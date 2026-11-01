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
                'categories' => fn ($query) => $query->active()->ordered()->with('translations'),
                'collections' => fn ($query) => $query->active()->ordered()->with('translations'),
            ])
            ->firstOrFail();

        $pageUrl = route('front.products.show', $product->slug);
        $brand = $product->brand?->is_active ? $product->brand : null;

        return view('product::front.show', [
            'product' => $product,
            'brand' => $brand,
            'title' => $brand ? "{$product->name} | {$brand->name}" : $product->name,
            'categoryCrumb' => $product->categories->first(),
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
        $values = [
            'karat' => $product->karat !== null ? __("product::product.karats.{$product->karat}") : null,
            'gold_color' => $product->gold_color !== null ? __("product::product.gold_colors.{$product->gold_color}") : null,
            'stone_type' => $product->stone_type !== null ? __("product::product.stone_types.{$product->stone_type}") : null,
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
     * Visible pieces that share an active category with this one, newest first.
     *
     * @return Collection<int, Product>
     */
    private function related(Product $product): Collection
    {
        $categoryIds = $product->categories->modelKeys();

        if ($categoryIds === []) {
            return $product->newCollection();
        }

        return Product::query()
            ->visible()
            ->forCard()
            ->whereKeyNot($product->getKey())
            ->whereHas('categories', fn ($query) => $query->whereIn('categories.id', $categoryIds))
            ->latestPublished()
            ->limit((int) config('catalog.front.related'))
            ->get();
    }
}
