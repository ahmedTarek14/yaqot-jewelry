<?php

namespace Modules\Product\Support;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Modules\Brand\Models\Brand;
use Modules\Category\Models\Category;
use Modules\Collection\Models\Collection as ProductCollection;
use Modules\Option\Models\ProductOption;

/**
 * The choices offered by the public filter form (product::front.partials.filters).
 * Categories, collections and brands are those the site lists (onSite(): active, with
 * visible jewelry; the bullion page lists brands with visible bars or coins instead);
 * gold colours and stone types are the owner's active options; karats come from
 * config/catalog.php.
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
     * The bullion page's taxonomy filter: the brands (as manufacturers) with visible bars or coins.
     *
     * @return array<string, Collection<int, Model>>
     */
    public static function bullionTaxonomies(): array
    {
        return ['brand' => Brand::onBullion()->ordered()->with('translations')->get()];
    }

    /**
     * attribute => [value => label], e.g. 'karat' => [18 => '18K', ...]. Gold colours and stone
     * types are the active options (slug => name in the current locale), so an inactive option
     * is never offered; the bullion profile offers the type (bar / coin) and the karat instead.
     *
     * @return array<string, array<int|string, string>>
     */
    public static function attributes(bool $bullion = false): array
    {
        $karats = [];
        foreach (config('catalog.karats') as $karat) {
            $karats[$karat] = __("product::product.karats.$karat");
        }

        if ($bullion) {
            $types = [];
            foreach (config('catalog.bullion') as $type) {
                $types[$type] = __("product::product.types_plural.$type");
            }

            return ['type' => $types, 'karat' => $karats];
        }

        return [
            'karat' => $karats,
            'gold_color' => self::options(ProductOption::GOLD_COLOR),
            'stone_type' => self::options(ProductOption::STONE_TYPE),
        ];
    }

    /**
     * @return array<string, string> slug => name of the group's active options, in the owner's order
     */
    private static function options(string $group): array
    {
        return ProductOption::query()->active()->inGroup($group)->ordered()->with('translations')->get()
            ->mapWithKeys(fn (ProductOption $option) => [$option->slug => (string) $option->name])
            ->all();
    }
}
