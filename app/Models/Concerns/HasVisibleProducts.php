<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

/**
 * Categories, collections and brands the public site lists: active, with at
 * least one product the site may show (Product::visible()). An active one
 * without visible products would only lead to an empty page.
 */
trait HasVisibleProducts
{
    public function scopeOnSite(Builder $query): void
    {
        $query->where($this->qualifyColumn('is_active'), true)
            ->whereHas('products', fn (Builder $products) => $products->visible());
    }
}
