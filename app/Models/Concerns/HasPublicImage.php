<?php

namespace App\Models\Concerns;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Removes the record's file on the public disk when the record is deleted or
 * the file is replaced. Deletion waits for the surrounding transaction to
 * commit, so a rolled-back write never loses a file.
 */
trait HasPublicImage
{
    public static function bootHasPublicImage(): void
    {
        static::updated(function (self $model) {
            $column = $model->publicImageColumn();

            if ($model->wasChanged($column)) {
                static::deletePublicFile($model->getOriginal($column));
            }
        });

        static::deleted(function (self $model) {
            if (! method_exists($model, 'isForceDeleting') || $model->isForceDeleting()) {
                static::deletePublicFile($model->getAttribute($model->publicImageColumn()));
            }
        });
    }

    protected function publicImageColumn(): string
    {
        return 'image_path';
    }

    public function publicImageUrl(): ?string
    {
        $path = $this->getAttribute($this->publicImageColumn());

        return $path ? Storage::disk('public')->url($path) : null;
    }

    protected static function deletePublicFile(?string $path): void
    {
        if ($path === null || $path === '') {
            return;
        }

        DB::afterCommit(fn () => Storage::disk('public')->delete($path));
    }
}
