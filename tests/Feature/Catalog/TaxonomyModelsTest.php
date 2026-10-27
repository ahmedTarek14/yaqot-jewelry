<?php

namespace Tests\Feature\Catalog;

use App\Exceptions\ModelInUseException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Brand\Models\Brand;
use Modules\Category\Models\Category;
use Modules\Collection\Models\Collection;
use Modules\Product\Models\Product;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TaxonomyModelsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{class-string<Model>}>
     */
    public static function taxonomies(): array
    {
        return [
            'category' => [Category::class],
            'collection' => [Collection::class],
            'brand' => [Brand::class],
        ];
    }

    private function attachProduct(Model $record): Product
    {
        if ($record instanceof Brand) {
            return Product::factory()->for($record)->create();
        }

        $product = Product::factory()->create();
        $record->products()->attach($product);

        return $product;
    }

    #[DataProvider('taxonomies')]
    public function test_cannot_be_deleted_while_products_are_attached(string $model): void
    {
        $record = $model::factory()->create();
        $this->attachProduct($record);

        $this->assertFalse($record->canBeDeleted());

        try {
            $record->delete();
            $this->fail('Expected ModelInUseException.');
        } catch (ModelInUseException $e) {
            $this->assertSame(__('admin.messages.in_use'), $e->getMessage());
        }

        $this->assertModelExists($record);
    }

    #[DataProvider('taxonomies')]
    public function test_trashed_products_still_block_deletion(string $model): void
    {
        $record = $model::factory()->create();
        $this->attachProduct($record)->delete();

        $this->expectException(ModelInUseException::class);

        $record->delete();
    }

    #[DataProvider('taxonomies')]
    public function test_deactivation_is_the_alternative_and_hides_it_from_the_active_scope(string $model): void
    {
        $record = $model::factory()->create();
        $this->attachProduct($record);

        $record->update(['is_active' => false]);

        $this->assertSame([], $model::active()->pluck('id')->all());
        $this->assertModelExists($record);
    }

    #[DataProvider('taxonomies')]
    public function test_can_be_deleted_without_products_and_translations_go_with_it(string $model): void
    {
        $record = $model::factory()->create();

        $this->assertTrue($record->canBeDeleted());
        $record->delete();

        $this->assertModelMissing($record);
        $this->assertSame(0, $record->translations()->count());
    }

    #[DataProvider('taxonomies')]
    public function test_active_and_ordered_scopes(string $model): void
    {
        $third = $model::factory()->create(['sort_order' => 2]);
        $first = $model::factory()->create(['sort_order' => 1]);
        $second = $model::factory()->create(['sort_order' => 1]);
        $model::factory()->inactive()->create(['sort_order' => 0]);

        $this->assertSame([$first->id, $second->id, $third->id], $model::active()->ordered()->pluck('id')->all());
    }

    #[DataProvider('taxonomies')]
    public function test_translations_fall_back_to_english(string $model): void
    {
        $record = $model::factory()->create(['en' => ['name' => 'Demo Fallback']]);
        $record->translations()->where('locale', 'ar')->delete();

        app()->setLocale('ar');

        $this->assertSame('Demo Fallback', $model::with('translations')->find($record->id)->name);
    }

    #[DataProvider('taxonomies')]
    public function test_the_database_also_refuses_query_level_deletes_while_products_are_attached(string $model): void
    {
        $record = $model::factory()->create();
        $this->attachProduct($record);

        try {
            $model::query()->whereKey($record->id)->delete();
            $this->fail('Expected a foreign key violation.');
        } catch (QueryException) {
        }

        $this->assertModelExists($record);
    }

    public function test_detaching_products_allows_deletion(): void
    {
        $category = Category::factory()->create();
        $product = $this->attachProduct($category);

        $category->products()->detach($product);
        $category->delete();

        $this->assertModelMissing($category);
        $this->assertModelExists($product);
    }

    public function test_force_deleting_a_product_detaches_it(): void
    {
        $category = Category::factory()->create();
        $collection = Collection::factory()->create();
        $product = $this->attachProduct($category);
        $collection->products()->attach($product);

        $product->forceDelete();

        $this->assertTrue($category->canBeDeleted());
        $this->assertTrue($collection->canBeDeleted());
    }

    public function test_collection_homepage_scope(): void
    {
        $shown = Collection::factory()->onHomepage()->create();
        Collection::factory()->create();

        $this->assertSame([$shown->id], Collection::active()->onHomepage()->pluck('id')->all());
    }

    public function test_optional_columns_are_nullable(): void
    {
        $category = Category::factory()->create(['en' => ['name' => 'Rings', 'description' => null]])->fresh();

        $this->assertNull($category->image_path);
        $this->assertNull($category->description);
        $this->assertNull(Collection::factory()->create()->fresh()->image_path);
    }
}
