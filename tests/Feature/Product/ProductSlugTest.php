<?php

namespace Tests\Feature\Product;

use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Modules\Brand\Models\Brand;
use Modules\Category\Models\Category;
use Modules\Collection\Models\Collection;
use Modules\Product\Models\Product;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ProductSlugTest extends TestCase
{
    use RefreshDatabase;

    public function test_slug_comes_from_the_english_name(): void
    {
        $product = Product::factory()->create(['en' => ['name' => 'Twisted Gold Ring']]);

        $this->assertSame('twisted-gold-ring', $product->slug);
    }

    public function test_slug_falls_back_to_the_sku_when_the_english_name_is_missing(): void
    {
        $product = new Product(['sku' => 'YQ-R-0042']);
        $product->translateOrNew('ar')->name = 'خاتم';
        $product->save();

        $this->assertSame('yq-r-0042', $product->slug);
    }

    public function test_slug_falls_back_to_the_sku_when_the_english_name_has_no_sluggable_characters(): void
    {
        $product = Product::factory()->create(['sku' => 'YQ-B-7', 'en' => ['name' => '!!! ???']]);

        $this->assertSame('yq-b-7', $product->slug);
    }

    public function test_translations_set_before_the_first_save_are_used_for_the_slug(): void
    {
        // The Phase 3 controller pattern: fill attributes and translations, then save once.
        $product = new Product(['sku' => 'YQ-N-1']);
        foreach (['ar' => 'عقد', 'en' => 'Pearl Necklace'] as $locale => $name) {
            $product->translateOrNew($locale)->name = $name;
        }
        $product->save();

        $this->assertSame('pearl-necklace', $product->slug);
    }

    #[DataProvider('sluggables')]
    public function test_saving_before_the_translations_is_rejected(string $model, array $attributes): void
    {
        $this->expectException(LogicException::class);

        try {
            (new $model($attributes))->save();
        } finally {
            $this->assertSame(0, $model::query()->withoutGlobalScopes()->count());
        }
    }

    /**
     * @return array<string, array{class-string, array<string, mixed>}>
     */
    public static function sluggables(): array
    {
        return [
            'product' => [Product::class, ['sku' => 'YQ-EARLY']],
            'category' => [Category::class, []],
            'collection' => [Collection::class, []],
            'brand' => [Brand::class, []],
        ];
    }

    public function test_slug_is_stable_when_the_name_or_sku_changes(): void
    {
        $product = Product::factory()->create(['sku' => 'YQ-1', 'en' => ['name' => 'Original Name']]);

        $product->fill(['sku' => 'YQ-2', 'en' => ['name' => 'Renamed Piece']])->save();

        $this->assertSame('original-name', $product->fresh()->slug);
    }

    public function test_slug_cannot_be_mass_assigned(): void
    {
        $product = Product::factory()->create(['en' => ['name' => 'Locked Slug']]);

        $product->fill(['slug' => 'hijacked'])->save();

        $this->assertSame('locked-slug', $product->fresh()->slug);
    }

    public function test_duplicate_names_get_unique_slugs_including_against_trashed_products(): void
    {
        $first = Product::factory()->create(['en' => ['name' => 'Same Name']]);
        $first->delete();
        $second = Product::factory()->create(['en' => ['name' => 'Same Name']]);
        $third = Product::factory()->create(['en' => ['name' => 'Same Name']]);

        $this->assertSame('same-name', $first->slug);
        $this->assertSame('same-name-2', $second->slug);
        $this->assertSame('same-name-3', $third->slug);
    }

    /**
     * @return array<string, array{class-string}>
     */
    public static function taxonomies(): array
    {
        return [
            'category' => [Category::class],
            'collection' => [Collection::class],
            'brand' => [Brand::class],
        ];
    }

    #[DataProvider('taxonomies')]
    public function test_taxonomy_slug_uses_english_then_arabic_and_is_never_empty(string $model): void
    {
        $english = $model::factory()->create(['en' => ['name' => 'Wedding Gold'], 'ar' => ['name' => 'ذهب الزفاف']]);
        $arabicOnly = $model::factory()->create(['en' => ['name' => '***'], 'ar' => ['name' => 'شبكة']]);
        $neither = $model::factory()->create(['en' => ['name' => '***'], 'ar' => ['name' => '###']]);
        $again = $model::factory()->create(['en' => ['name' => '***'], 'ar' => ['name' => '###']]);

        $this->assertSame('wedding-gold', $english->slug);
        $this->assertNotSame('', $arabicOnly->slug);
        $this->assertMatchesRegularExpression('/^[a-z0-9-]+$/', $arabicOnly->slug);
        $this->assertSame(strtolower(class_basename($model)), $neither->slug);
        $this->assertSame(strtolower(class_basename($model)).'-2', $again->slug);

        $english->fill(['en' => ['name' => 'Renamed']])->save();
        $this->assertSame('wedding-gold', $english->fresh()->slug);
    }
}
