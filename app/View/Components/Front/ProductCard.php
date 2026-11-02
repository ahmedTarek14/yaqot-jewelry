<?php

namespace App\View\Components\Front;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Modules\Product\Models\Product;

/**
 * A product in a public grid: primary image in a .yq-frame, brand, name, karat
 * and weight (no prices). Load products with Product::forCard() so nothing
 * here queries. Used inside <x-front.product-grid>.
 */
class ProductCard extends Component
{
    public ?string $url;

    public ?string $imageUrl;

    public ?string $brand;

    /** @var array<int, string> */
    public array $details;

    /**
     * @param  bool  $eager  load the image now instead of lazily (a card above the fold)
     * @param  bool  $priority  the first card of a listing: its image is the page's likely LCP element
     */
    public function __construct(public Product $product, public bool $eager = false, public bool $priority = false)
    {
        $this->url = route('front.products.show', $product->slug);
        $this->imageUrl = $product->primaryImage?->url;
        // A deactivated brand is hidden from the public site, cards included.
        $this->brand = $product->brand?->is_active ? $product->brand->name : null;
        $this->details = array_values(array_filter([
            $product->type !== Product::JEWELRY ? __("product::product.types.{$product->type}") : null,
            $product->karat !== null ? __("product::product.karats.{$product->karat}") : null,
            $product->weight_label,
        ]));
    }

    public function render(): View
    {
        return view('components.front.product-card');
    }
}
