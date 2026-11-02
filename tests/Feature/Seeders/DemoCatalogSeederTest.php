<?php

namespace Tests\Feature\Seeders;

use Database\Seeders\DemoCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Modules\Branch\Models\Branch;
use Modules\Brand\Models\Brand;
use Modules\Category\Models\Category;
use Modules\Collection\Models\Collection;
use Modules\Product\Models\Product;
use Modules\Product\Models\ProductImage;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class DemoCatalogSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    /**
     * @return array<string, array{string}>
     */
    public static function refusedEnvironments(): array
    {
        return [
            'production' => ['production'],
            'staging' => ['staging'],
        ];
    }

    #[DataProvider('refusedEnvironments')]
    public function test_refuses_to_run_outside_local_and_testing(string $environment): void
    {
        $this->app['env'] = $environment;

        try {
            $this->app->make(DemoCatalogSeeder::class)->run();
            $this->fail('Expected the seeder to refuse.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('local or testing', $e->getMessage());
        }

        $this->assertSame(0, Product::withTrashed()->count());
        $this->assertSame(0, Brand::count());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_the_production_command_is_refused(): void
    {
        $this->app['env'] = 'production';

        try {
            $this->artisan('db:seed', ['--class' => DemoCatalogSeeder::class, '--force' => true])->run();
            $this->fail('Expected db:seed to refuse.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('local or testing', $e->getMessage());
        }

        $this->assertSame(0, Product::withTrashed()->count());
    }

    public function test_seeds_an_obviously_fake_catalog_with_image_files(): void
    {
        $this->seed(DemoCatalogSeeder::class);

        $this->assertSame(24, Product::jewelry()->count());
        $this->assertSame(7, Product::bullion()->count());
        $this->assertSame(3, Brand::count());
        $this->assertSame(4, Category::count());
        $this->assertSame(3, Collection::count());
        $this->assertSame(2, Branch::count());

        $this->assertSame(0, Product::where('sku', 'not like', 'DEMO-%')->count());
        $this->assertGreaterThan(0, Product::visible()->count());
        $this->assertLessThan(31, Product::visible()->count());

        // Jewelry carries the owner's options; bars and coins carry neither, and use DEMO-BAR-/DEMO-COIN- SKUs.
        $this->assertSame(0, Product::jewelry()->where(fn ($q) => $q->whereNull('gold_color_id')->orWhereNull('stone_type_id'))->count());
        $this->assertSame(0, Product::bullion()->where(fn ($q) => $q->whereNotNull('gold_color_id')->orWhereNotNull('stone_type_id'))->count());
        $this->assertSame(4, Product::where('sku', 'like', 'DEMO-BAR-%')->where('type', 'bar')->count());
        $this->assertSame(3, Product::where('sku', 'like', 'DEMO-COIN-%')->where('type', 'coin')->count());
        $this->assertSame(1, Product::bullion()->where('is_published', false)->count());
        $this->assertGreaterThan(0, Product::bullion()->visible()->count());

        foreach (ProductImage::all() as $image) {
            Storage::disk('public')->assertExists($image->path);
        }

        foreach (Product::with('translations')->get() as $product) {
            $this->assertStringStartsWith('Demo ', $product->translate('en')->name);
        }
    }

    public function test_running_twice_does_not_duplicate(): void
    {
        $this->seed(DemoCatalogSeeder::class);
        $this->seed(DemoCatalogSeeder::class);

        $this->assertSame(31, Product::count());
        $this->assertSame(3, Brand::count());
    }

    public function test_a_catalog_seeded_before_bullion_only_gains_the_bars_and_coins(): void
    {
        $this->seed(DemoCatalogSeeder::class);
        Product::bullion()->get()->each->forceDelete();
        $this->assertSame(24, Product::count());

        $this->seed(DemoCatalogSeeder::class);

        $this->assertSame(24, Product::jewelry()->count());
        $this->assertSame(7, Product::bullion()->count());
        $this->assertSame(3, Brand::count());
    }
}
