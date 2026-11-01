<?php

namespace Modules\Product\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * The public catalog's query parameters, each checked against an allowlist:
 *   q                         search term: the product name in any locale, or the SKU
 *   category, collection,     a slug among the selectable options passed in (active, with
 *   brand                     visible products); unknown slugs are ignored
 *   karat, gold_color,        a value from config/catalog.php
 *   stone_type
 *   weight_min, weight_max    grams, 0 to 99999.999 (swapped when given in the wrong order)
 *   sort                      newest (default), name, weight_asc, weight_desc
 * Invalid values are dropped silently, so a hand-edited URL never errors or reaches the query.
 * Category, collection and brand pages (Phase 4b) pass options without their own filter.
 */
final class CatalogFilters
{
    public const SORTS = ['newest', 'name', 'weight_asc', 'weight_desc'];

    public const DEFAULT_SORT = 'newest';

    /** Product attribute filters => config('catalog.*') allowlist. */
    public const ATTRIBUTES = [
        'karat' => 'karats',
        'gold_color' => 'gold_colors',
        'stone_type' => 'stone_types',
    ];

    /** decimal(8,3) */
    private const MAX_WEIGHT = 99999.999;

    /**
     * @param  array<string, Model>  $taxonomies  filter name => the selected model
     * @param  array<string, int|string>  $attributes  attribute => the selected value
     */
    private function __construct(
        public readonly ?string $search,
        public readonly array $taxonomies,
        public readonly array $attributes,
        public readonly ?string $weightMin,
        public readonly ?string $weightMax,
        public readonly string $sort,
    ) {}

    /**
     * @param  array<string, Collection<int, Model>>  $options  taxonomy filter (category, collection,
     *                                                          brand) => the models it may select
     */
    public static function fromRequest(Request $request, array $options = []): self
    {
        $search = $request->query('q');
        $search = is_string($search) ? mb_substr(trim($search), 0, config('catalog.front.search_max_length')) : '';

        $taxonomies = [];
        foreach ($options as $name => $models) {
            $slug = $request->query($name);
            $model = is_string($slug) ? $models->firstWhere('slug', $slug) : null;

            if ($model !== null) {
                $taxonomies[$name] = $model;
            }
        }

        $attributes = [];
        foreach (self::ATTRIBUTES as $attribute => $list) {
            $value = $request->query($attribute);
            $allowed = config("catalog.$list");

            if (! is_string($value)) {
                continue;
            }

            if (is_int($allowed[0] ?? null)) {
                $value = filter_var($value, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE);
            }

            if (in_array($value, $allowed, true)) {
                $attributes[$attribute] = $value;
            }
        }

        $min = self::weight($request->query('weight_min'));
        $max = self::weight($request->query('weight_max'));

        if ($min !== null && $max !== null && (float) $min > (float) $max) {
            [$min, $max] = [$max, $min];
        }

        $sort = $request->query('sort');
        $sort = is_string($sort) && in_array($sort, self::SORTS, true) ? $sort : self::DEFAULT_SORT;

        return new self($search === '' ? null : $search, $taxonomies, $attributes, $min, $max, $sort);
    }

    /**
     * Adds the search, filters and sort to a products query.
     */
    public function apply(Builder $query): void
    {
        $model = $query->getModel();

        if ($this->search !== null) {
            $pattern = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $this->search).'%';

            // Every locale's name, whatever the page language: visitors often type Arabic on /en.
            $query->where(fn (Builder $query) => $query
                ->whereHas('translations', fn (Builder $translation) => $translation
                    ->whereIn('locale', config('translatable.locales'))
                    ->whereRaw("name like ? escape '!'", [$pattern]))
                ->orWhereRaw($model->qualifyColumn('sku')." like ? escape '!'", [$pattern]));
        }

        foreach ($this->taxonomies as $name => $selected) {
            match ($name) {
                'brand' => $query->where($model->qualifyColumn('brand_id'), $selected->getKey()),
                'category' => $query->whereHas('categories', fn (Builder $q) => $q->whereKey($selected->getKey())),
                'collection' => $query->whereHas('collections', fn (Builder $q) => $q->whereKey($selected->getKey())),
            };
        }

        foreach ($this->attributes as $attribute => $value) {
            $query->where($model->qualifyColumn($attribute), $value);
        }

        if ($this->weightMin !== null) {
            $query->where($model->qualifyColumn('weight_grams'), '>=', $this->weightMin);
        }

        if ($this->weightMax !== null) {
            $query->where($model->qualifyColumn('weight_grams'), '<=', $this->weightMax);
        }

        $weight = $model->qualifyColumn('weight_grams');

        match ($this->sort) {
            'name' => $query->orderByTranslation('name')->orderBy($model->qualifyColumn('id')),
            // Products without a weight go last in both directions.
            'weight_asc' => $query->orderByRaw("$weight is null")->orderBy($weight)->orderBy($model->qualifyColumn('id')),
            'weight_desc' => $query->orderByRaw("$weight is null")->orderByDesc($weight)->orderByDesc($model->qualifyColumn('id')),
            default => $query->latestPublished(),
        };
    }

    /**
     * The form value of a filter: a slug for taxonomies, the raw value otherwise.
     */
    public function value(string $name): ?string
    {
        return match (true) {
            $name === 'q' => $this->search,
            $name === 'weight_min' => $this->weightMin,
            $name === 'weight_max' => $this->weightMax,
            $name === 'sort' => $this->sort,
            isset($this->taxonomies[$name]) => $this->taxonomies[$name]->getAttribute('slug'),
            isset($this->attributes[$name]) => (string) $this->attributes[$name],
            default => null,
        };
    }

    /**
     * How many filters are applied (the search counts as one; the sort does not).
     */
    public function activeCount(): int
    {
        return ($this->search !== null ? 1 : 0)
            + count($this->taxonomies)
            + count($this->attributes)
            + ($this->weightMin !== null ? 1 : 0)
            + ($this->weightMax !== null ? 1 : 0);
    }

    public function isFiltered(): bool
    {
        return $this->activeCount() > 0;
    }

    /**
     * The page without any filter; a chosen sort is kept.
     */
    public function clearUrl(string $action): string
    {
        return $this->sort === self::DEFAULT_SORT ? $action : $action.'?'.http_build_query(['sort' => $this->sort]);
    }

    /**
     * A weight in grams as a plain decimal string, or null when it is not one.
     */
    private static function weight(mixed $value): ?string
    {
        if (! is_string($value) || ! preg_match('/^\d{1,5}(?:\.\d{1,3})?$/', trim($value))) {
            return null;
        }

        $value = (float) trim($value);

        return $value <= self::MAX_WEIGHT ? rtrim(rtrim(number_format($value, 3, '.', ''), '0'), '.') : null;
    }
}
