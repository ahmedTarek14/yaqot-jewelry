<?php

namespace Database\Seeders;

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

        if (Product::withTrashed()->where('sku', 'like', 'DEMO-%')->exists()) {
            $this->command?->warn('Demo catalog already seeded; nothing to do.');

            return;
        }

        $images = new ImageManager(new Driver);

        DB::transaction(function () use ($images) {
            $brands = Brand::factory()->count(3)->sequence(fn ($s) => ['sort_order' => $s->index])->create();
            $categories = Category::factory()->count(4)->sequence(fn ($s) => ['sort_order' => $s->index])->create();
            $collections = Collection::factory()->count(3)
                ->sequence(fn ($s) => ['sort_order' => $s->index, 'show_on_homepage' => $s->index < 2])
                ->create();

            Branch::factory()->count(2)->sequence(fn ($s) => ['sort_order' => $s->index])->create();

            for ($i = 0; $i < 24; $i++) {
                $product = Product::factory()
                    ->state([
                        // A few drafts and house (unbranded) pieces, so filters and the visibility scope have something to exclude.
                        'is_published' => $i % 8 !== 7,
                        'is_featured' => $i % 5 === 0,
                        'brand_id' => $i % 4 === 3 ? null : $brands[$i % 3]->id,
                    ])
                    ->create();

                $product->categories()->attach($categories[$i % 4]);
                $product->collections()->attach($collections[$i % 3]);

                for ($sort = 0; $sort < 1 + $i % 3; $sort++) {
                    $path = "products/{$product->id}/".Str::uuid().'.webp';
                    $placeholder = $images->create(800, 1000)
                        ->fill(self::PLACEHOLDER_COLORS[($i + $sort) % count(self::PLACEHOLDER_COLORS)]);

                    Storage::disk('public')->put($path, (string) $placeholder->toWebp(80));
                    $product->images()->create(['path' => $path, 'sort_order' => $sort]);
                }
            }
        });
    }
}
