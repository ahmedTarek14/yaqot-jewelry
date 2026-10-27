<?php

namespace App\Models\Concerns;

use App\Exceptions\ModelInUseException;

/**
 * Categories, collections and brands cannot be deleted while products
 * (including trashed ones, which can be restored) still reference them.
 * The admin deactivates them instead.
 */
trait HasProductsGuard
{
    public static function bootHasProductsGuard(): void
    {
        static::deleting(function (self $model) {
            $total = $model->products()->withTrashed()->count();

            if ($total > 0) {
                throw new ModelInUseException($total, $model->products()->onlyTrashed()->count());
            }
        });
    }

    public function canBeDeleted(): bool
    {
        return ! $this->products()->withTrashed()->exists();
    }
}
