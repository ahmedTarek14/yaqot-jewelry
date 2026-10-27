<?php

namespace App\Models\Concerns;

use Cocur\Slugify\Slugify;
use Cviebrock\EloquentSluggable\Sluggable;
use LogicException;

/**
 * One slug per record, shared by both locales and never changed after creation.
 *
 * The model lists its slug sources in order of preference; the first one that
 * produces a non-empty slug wins, so a slug is never empty.
 */
trait HasStableSlug
{
    use Sluggable;

    /**
     * Candidate strings for the slug, in order of preference.
     *
     * @return array<int, string|null>
     */
    abstract protected function slugSources(): array;

    public static function bootHasStableSlug(): void
    {
        // The slug is fixed at creation, so saving the row before its translations would
        // permanently slug it from the fallback. Set translations first, then save once.
        static::creating(function (self $model) {
            if (! $model->relationLoaded('translations') || $model->translations->isEmpty()) {
                throw new LogicException(class_basename($model).' must be created together with its translations.');
            }
        });
    }

    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => 'slug_source',
                'onUpdate' => false,
                'unique' => true,
                // A soft-deleted row keeps its slug, so it can be restored without a clash.
                'includeTrashed' => true,
                'maxLength' => 180,
            ],
        ];
    }

    public function getSlugSourceAttribute(): string
    {
        $slugify = new Slugify;

        foreach ($this->slugSources() as $source) {
            if ($source !== null && $slugify->slugify($source) !== '') {
                return $source;
            }
        }

        return class_basename($this);
    }
}
