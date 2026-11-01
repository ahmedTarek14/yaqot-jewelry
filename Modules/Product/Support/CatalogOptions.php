<?php

namespace Modules\Product\Support;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Modules\Brand\Models\Brand;
use Modules\Category\Models\Category;
use Modules\Collection\Models\Collection as ProductCollection;

/**
 * The choices offered by the public filter form (product::front.partials.filters).
 * Categories, collections and brands are those the site lists (onSite(): active, with
 * visible products); attribute values come from config/catalog.php.
 */
final class CatalogOptions
{
    /**
     * @param  array<int, string>  $except  filters a page fixes itself (e.g. 'category' on a category page)
     * @return array<string, Collection<int, Model>>
     */
    public static function taxonomies(array $except = []): array
    {
        $models = [
            'category' => Category::class,
            'collection' => ProductCollection::class,
            'brand' => Brand::class,
        ];

        $options = [];
        foreach (array_diff_key($models, array_flip($except)) as $name => $model) {
            $options[$name] = $model::onSite()->ordered()->with('translations')->get();
        }

        return $options;
    }

    /**
     * attribute => [value => label], e.g. 'karat' => [18 => '18K', ...].
     *
     * @return array<string, array<int|string, string>>
     */
    public static function attributes(): array
    {
        $options = [];
        foreach (CatalogFilters::ATTRIBUTES as $attribute => $list) {
            foreach (config("catalog.$list") as $value) {
                $options[$attribute][$value] = __("product::product.$list.$value");
            }
        }

        return $options;
    }
}
