<?php

namespace Modules\Product\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Modules\Option\Models\ProductOption;

/**
 * A public product listing's query parameters, each checked against an allowlist:
 *   q                         search term: the product name in any locale, or the SKU
 *   category, collection,     a slug among the selectable options passed in (active, with
 *   brand                     visible products); unknown slugs are ignored
 *   karat                     a value from config/catalog.php
 *   gold_color, stone_type    the slug of an active option (jewelry only)
 *   type                      bar | coin (bullion only)
 *   weight_min, weight_max    grams, 0 to 99999.999 (swapped when given in the wrong order)
 *   sort                      jewelry: newest (default), name, weight_asc, weight_desc;
 *                             bullion: the same, with weight_asc first and the default
 * Invalid values are dropped silently, so a hand-edited URL never errors or reaches the query.
 * Category, collection and brand pages (Phase 4b) pass options without their own filter.
 *
 * Two profiles: jewelry (the default: /catalog and the taxonomy pages, which never list bars or
 * coins) and bullion (/bullion, which lists only bars and coins).
 */
final class CatalogFilters
{
    public const JEWELRY_SORTS = ['newest', 'name', 'weight_asc', 'weight_desc'];

    public const BULLION_SORTS = ['weight_asc', 'weight_desc', 'newest', 'name'];

    /** Attribute filters per profile (chips in the form). */
    public const JEWELRY_ATTRIBUTES = ['karat', 'gold_color', 'stone_type'];

    public const BULLION_ATTRIBUTES = ['type', 'karat'];

    /** Option filters => the products column holding the option. */
    private const OPTION_COLUMNS = [
        'gold_color' => ProductOption::GOLD_COLOR,
        'stone_type' => ProductOption::STONE_TYPE,
    ];

    /** decimal(8,3) */
    private const MAX_WEIGHT = 99999.999;

    /**
     * @param  array<string, Model>  $taxonomies  filter name => the selected model
     * @param  array<string, string>  $attributes  attribute => the selected value (a slug for options)
     */
    private function __construct(
        public readonly bool $bullion,
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
     * @param  array<string, array<int|string, string>>  $attributes  CatalogOptions::attributes():
     *                                                                attribute => [value => label]
     */
    public static function fromRequest(Request $request, array $options, array $attributes, bool $bullion = false): self
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

        $selected = [];
        foreach (self::attributeNames($bullion) as $attribute) {
            $value = $request->query($attribute);

            // Array keys: "18" is 18, but "018" and "18.0" are not.
            if (is_string($value) && array_key_exists($value, $attributes[$attribute] ?? [])) {
                $selected[$attribute] = $value;
            }
        }

        $min = self::weight($request->query('weight_min'));
        $max = self::weight($request->query('weight_max'));

        if ($min !== null && $max !== null && (float) $min > (float) $max) {
            [$min, $max] = [$max, $min];
        }

        $sort = $request->query('sort');
        $sort = is_string($sort) && in_array($sort, self::sorts($bullion), true) ? $sort : self::defaultSortFor($bullion);

        return new self($bullion, $search === '' ? null : $search, $taxonomies, $selected, $min, $max, $sort);
    }

    /**
     * @return array<int, string>
     */
    public static function attributeNames(bool $bullion): array
    {
        return $bullion ? self::BULLION_ATTRIBUTES : self::JEWELRY_ATTRIBUTES;
    }

    /**
     * @return array<int, string>
     */
    public static function sorts(bool $bullion): array
    {
        return $bullion ? self::BULLION_SORTS : self::JEWELRY_SORTS;
    }

    public static function defaultSortFor(bool $bullion): string
    {
        return self::sorts($bullion)[0];
    }

    /**
     * @return array<int, string>
     */
    public function availableSorts(): array
    {
        return self::sorts($this->bullion);
    }

    public function defaultSort(): string
    {
        return self::defaultSortFor($this->bullion);
    }

    /**
     * Adds the profile's product type, the search, filters and sort to a products query.
     */
    public function apply(Builder $query): void
    {
        $model = $query->getModel();

        // The type scope comes first: /catalog and the taxonomy pages never list bars or coins.
        if (! $this->bullion) {
            $query->jewelry();
        } elseif (isset($this->attributes['type'])) {
            $query->where($model->qualifyColumn('type'), $this->attributes['type']);
        } else {
            $query->bullion();
        }

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
            if ($attribute === 'karat') {
                $query->where($model->qualifyColumn('karat'), (int) $value);
            } elseif (isset(self::OPTION_COLUMNS[$attribute])) {
                // By slug: the id is looked up once by the database. Inactive options are not in the
                // allowlist, so they never get here.
                $query->whereIn(
                    $model->qualifyColumn(ProductOption::COLUMNS[self::OPTION_COLUMNS[$attribute]]),
                    ProductOption::query()->select('id')->inGroup(self::OPTION_COLUMNS[$attribute])->where('slug', $value),
                );
            }
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
     * The form value of a filter: a slug for taxonomies and options, the raw value otherwise.
     */
    public function value(string $name): ?string
    {
        return match (true) {
            $name === 'q' => $this->search,
            $name === 'weight_min' => $this->weightMin,
            $name === 'weight_max' => $this->weightMax,
            $name === 'sort' => $this->sort,
            isset($this->taxonomies[$name]) => $this->taxonomies[$name]->getAttribute('slug'),
            isset($this->attributes[$name]) => $this->attributes[$name],
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
        return $this->sort === $this->defaultSort() ? $action : $action.'?'.http_build_query(['sort' => $this->sort]);
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
