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
            if (! $model->canBeDeleted()) {
                throw new ModelInUseException;
            }
        });
    }

    public function canBeDeleted(): bool
    {
        return ! $this->products()->withTrashed()->exists();
    }
}
