<?php

namespace Database\Seeders;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Modules\Branch\Models\Branch;
use Modules\Brand\Models\Brand;
use Modules\Category\Models\Category;
use Modules\Collection\Models\Collection;
use Modules\Option\Models\ProductOption;
use Modules\Product\Models\Product;
use RuntimeException;

/**
 * Obviously fake catalog for local development:
 * php artisan db:seed --class=DemoCatalogSeeder
 *
 * Refuses to run outside the local and testing environments. Real categories,
 * collections and brands are seeded only from lists the owner approves.
 */
class DemoCatalogSeeder extends Seeder
{
    public const ALLOWED_ENVIRONMENTS = ['local', 'testing'];

    private const PLACEHOLDER_COLORS = ['#1a1a1a', '#232323', '#34280f', '#433313'];

    public function run(): void
    {
        if (! app()->environment(self::ALLOWED_ENVIRONMENTS)) {
            throw new RuntimeException('DemoCatalogSeeder only runs in the local or testing environment.');
        }

        $demo = fn () => Product::withTrashed()->where('sku', 'like', 'DEMO-%');
        $hasBullion = $demo()->where(fn ($query) => $query->where('sku', 'like', 'DEMO-BAR-%')->orWhere('sku', 'like', 'DEMO-COIN-%'))->exists();
        $hasJewelry = $demo()->where('sku', 'not like', 'DEMO-BAR-%')->where('sku', 'not like', 'DEMO-COIN-%')->exists();

        if ($hasJewelry && $hasBullion) {
            $this->command?->warn('Demo catalog already seeded; nothing to do.');

            return;
        }

        $images = new ImageManager(new Driver);

        // A catalog seeded before bars and coins existed only gets them added.
        if ($hasJewelry) {
            DB::transaction(fn () => $this->seedBullion($images, Brand::query()->ordered()->limit(3)->get()));

            return;
        }

        DB::transaction(function () use ($images) {
            $brands = Brand::factory()->count(3)->sequence(fn ($s) => ['sort_order' => $s->index])->create();
            $categories = Category::factory()->count(4)->sequence(fn ($s) => ['sort_order' => $s->index])->create();
            $collections = Collection::factory()->count(3)
                ->sequence(fn ($s) => ['sort_order' => $s->index, 'show_on_homepage' => $s->index < 2])
                ->create();

            Branch::factory()->count(2)->sequence(fn ($s) => ['sort_order' => $s->index])->create();

            // The owner's options come from the data migration; a database without any gets two demo ones.
            $goldColors = $this->options(ProductOption::GOLD_COLOR);
            $stoneTypes = $this->options(ProductOption::STONE_TYPE);

            for ($i = 0; $i < 24; $i++) {
                $product = Product::factory()
                    ->state([
                        'gold_color_id' => $goldColors[$i % $goldColors->count()]->id,
                        'stone_type_id' => $stoneTypes[($i + 1) % $stoneTypes->count()]->id,
                        // A few drafts and house (unbranded) pieces, so filters and the visibility scope have something to exclude.
                        'is_published' => $i % 8 !== 7,
                        'is_featured' => $i % 5 === 0,
                        'brand_id' => $i % 4 === 3 ? null : $brands[$i % 3]->id,
                    ])
                    ->create();

                $product->categories()->attach($categories[$i % 4]);
                $product->collections()->attach($collections[$i % 3]);

                $this->placeholderImages($product, $images, 1 + $i % 3, $i);
            }

            $this->seedBullion($images, $brands);
        });
    }

    /**
     * Obviously fake bars and coins (DEMO-BAR- / DEMO-COIN- SKUs), with no gold colour or stones.
     * One house piece and one draft, so /bullion has something to filter and to exclude.
     *
     * @param  EloquentCollection<int, Brand>  $brands
     */
    private function seedBullion(ImageManager $images, EloquentCollection $brands): void
    {
        foreach (['bar' => 4, 'coin' => 3] as $type => $count) {
            for ($i = 0; $i < $count; $i++) {
                $product = Product::factory()->{$type}()
                    ->state([
                        'is_published' => ! ($type === 'bar' && $i === 3),
                        'brand_id' => $i === 2 || $brands->isEmpty() ? null : $brands[$i % $brands->count()]->id,
                    ])
                    ->create();

                $this->placeholderImages($product, $images, 1 + $i % 2, $i);
            }
        }
    }

    /**
     * @return EloquentCollection<int, ProductOption>
     */
    private function options(string $group): EloquentCollection
    {
        $options = ProductOption::query()->active()->inGroup($group)->ordered()->get();

        return $options->isNotEmpty()
            ? $options
            : ProductOption::factory()->count(2)->state(['group' => $group])->create();
    }

    private function placeholderImages(Product $product, ImageManager $images, int $count, int $seed): void
    {
        for ($sort = 0; $sort < $count; $sort++) {
            $path = "products/{$product->id}/".Str::uuid().'.webp';
            $placeholder = $images->create(800, 1000)
                ->fill(self::PLACEHOLDER_COLORS[($seed + $sort) % count(self::PLACEHOLDER_COLORS)]);

            Storage::disk('public')->put($path, (string) $placeholder->toWebp(80));
            $product->images()->create(['path' => $path, 'sort_order' => $sort]);
        }
    }
}
