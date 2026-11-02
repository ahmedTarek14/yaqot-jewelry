<?php

namespace Tests\Feature\Product;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\LazyLoadingViolationException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Modules\Brand\Models\Brand;
use Modules\Category\Models\Category;
use Modules\Collection\Models\Collection;
use Modules\Option\Models\ProductOption;
use Modules\Product\Models\Product;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ProductModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_relationships_work_in_both_directions(): void
    {
        $brand = Brand::factory()->create();
        $category = Category::factory()->create();
        $collection = Collection::factory()->create();

        $product = Product::factory()->for($brand)->create();
        $product->categories()->attach($category);
        $product->collections()->attach($collection);

        $product = Product::with(['brand', 'categories', 'collections'])->find($product->id);

        $this->assertTrue($product->brand->is($brand));
        $this->assertTrue($product->categories->first()->is($category));
        $this->assertTrue($product->collections->first()->is($collection));
        $this->assertTrue($brand->products()->first()->is($product));
        $this->assertTrue($category->products()->first()->is($product));
        $this->assertTrue($collection->products()->first()->is($product));
    }

    public function test_sku_is_unique(): void
    {
        Product::factory()->create(['sku' => 'DEMO-UNIQUE']);

        $this->expectException(UniqueConstraintViolationException::class);

        Product::factory()->create(['sku' => 'DEMO-UNIQUE']);
    }

    public function test_sku_stays_unique_against_trashed_products(): void
    {
        Product::factory()->create(['sku' => 'DEMO-TRASHED'])->delete();

        $this->expectException(UniqueConstraintViolationException::class);

        Product::factory()->create(['sku' => 'DEMO-TRASHED']);
    }

    public function test_optional_attributes_are_nullable(): void
    {
        $product = Product::factory()->create([
            'brand_id' => null,
            'karat' => null,
            'gold_color_id' => null,
            'stone_type_id' => null,
            'weight_grams' => null,
            'en' => ['name' => 'Demo Plain', 'description' => null],
            'ar' => ['name' => 'قطعة بسيطة', 'description' => null],
        ])->fresh();

        $this->assertNull($product->brand_id);
        $this->assertNull($product->karat);
        $this->assertNull($product->gold_color_id);
        $this->assertNull($product->stone_type_id);
        $this->assertSame('jewelry', $product->type);
        $this->assertNull($product->weight_grams);
        $this->assertNull($product->description);
        $this->assertNull($product->published_at);
        $this->assertFalse($product->is_published);
        $this->assertFalse($product->is_featured);
    }

    public function test_casts(): void
    {
        $product = Product::factory()->create(['karat' => 21, 'weight_grams' => 4.5])->fresh();

        $this->assertSame(21, $product->karat);
        $this->assertSame('4.500', $product->weight_grams);
    }

    public function test_translations_are_per_locale_and_fall_back_to_english(): void
    {
        $product = Product::factory()->create([
            'en' => ['name' => 'Demo Ring'],
            'ar' => ['name' => 'خاتم تجريبي'],
        ]);
        $englishOnly = Product::factory()->create([
            'en' => ['name' => 'Demo English Only'],
        ]);
        $englishOnly->translations()->where('locale', 'ar')->delete();

        app()->setLocale('ar');
        $this->assertSame('خاتم تجريبي', $product->fresh()->name);
        $this->assertSame('Demo English Only', $englishOnly->fresh()->name);

        app()->setLocale('en');
        $this->assertSame('Demo Ring', $product->fresh()->name);
    }

    public function test_publishing_sets_published_at_once(): void
    {
        $product = Product::factory()->create();
        $this->assertNull($product->published_at);

        $product->update(['is_published' => true]);
        $publishedAt = $product->fresh()->published_at;
        $this->assertNotNull($publishedAt);

        $this->travel(1)->day();
        $product->update(['is_published' => false]);
        $product->update(['is_published' => true]);

        $this->assertTrue($product->fresh()->published_at->equalTo($publishedAt));
    }

    /**
     * @return array<string, array{string, mixed}>
     */
    public static function invalidAllowlistValues(): array
    {
        return [
            'karat' => ['karat', 22],
            'type' => ['type', 'ring'],
            'type wrong case' => ['type', 'Bar'],
            'fractional karat' => ['karat', 18.9],
            'karat with junk' => ['karat', '21abc'],
        ];
    }

    #[DataProvider('invalidAllowlistValues')]
    public function test_attributes_outside_the_allowlist_are_rejected(string $attribute, mixed $value): void
    {
        $this->expectException(InvalidArgumentException::class);

        Product::factory()->create([$attribute => $value]);
    }

    public function test_allowlisted_values_are_accepted(): void
    {
        foreach (config('catalog.karats') as $karat) {
            $this->assertSame($karat, Product::factory()->create(['karat' => $karat])->karat);
        }

        $this->assertSame(21, Product::factory()->create(['karat' => '21'])->fresh()->karat);
        foreach (config('catalog.types') as $type) {
            $this->assertSame($type, Product::factory()->create(['type' => $type])->type);
        }
    }

    public function test_options_and_type_scopes(): void
    {
        $rose = ProductOption::where('slug', 'rose')->sole();
        $pearl = ProductOption::where('slug', 'pearl')->sole();
        $ring = Product::factory()->withGoldColor($rose)->withStoneType($pearl)->create();
        $bar = Product::factory()->bar()->create();
        $coin = Product::factory()->coin()->create();

        $ring->load(['goldColor.translations', 'stoneType.translations']);
        $this->assertTrue($ring->goldColor->is($rose));
        $this->assertTrue($ring->stoneType->is($pearl));
        $this->assertTrue($ring->isJewelry());
        $this->assertFalse($bar->isJewelry());

        $this->assertSame([$ring->id], Product::jewelry()->pluck('id')->all());
        $this->assertEqualsCanonicalizing([$bar->id, $coin->id], Product::bullion()->pluck('id')->all());
        $this->assertStringStartsWith('DEMO-BAR-', $bar->sku);
        $this->assertNull($bar->gold_color_id);
    }

    public function test_a_value_removed_from_the_allowlist_does_not_block_saving_other_changes(): void
    {
        $product = Product::factory()->create(['karat' => 18]);
        config(['catalog.karats' => [21, 24]]);

        $product->update(['is_featured' => true]);

        $this->assertSame(18, $product->fresh()->karat);
    }

    public function test_lazy_loading_is_prevented_and_eager_loading_works(): void
    {
        $this->assertTrue(Model::preventsLazyLoading());

        Product::factory()->count(3)->for(Brand::factory())->withImages(2)->create();

        $products = Product::with(['translations', 'brand.translations', 'primaryImage'])->get();
        foreach ($products as $product) {
            $this->assertNotEmpty($product->name);
            $this->assertNotEmpty($product->brand->name);
            $this->assertSame($product->name, $product->primaryImage->alt);
        }

        $this->expectException(LazyLoadingViolationException::class);
        Product::all()->first()->brand;
    }
}
