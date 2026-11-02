<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

/**
 * Categories, collections and brands the public site lists: active, with at
 * least one piece of jewelry the site may show (Product::visible()). Their pages
 * list jewelry only, so an active one with nothing but bars and coins would only
 * lead to an empty page. onBullion() is the bullion page's version for brands.
 */
trait HasVisibleProducts
{
    public function scopeOnSite(Builder $query): void
    {
        $query->where($this->qualifyColumn('is_active'), true)
            ->whereHas('products', fn (Builder $products) => $products->visible()->jewelry());
    }

    public function scopeOnBullion(Builder $query): void
    {
        $query->where($this->qualifyColumn('is_active'), true)
            ->whereHas('products', fn (Builder $products) => $products->visible()->bullion());
    }
}
