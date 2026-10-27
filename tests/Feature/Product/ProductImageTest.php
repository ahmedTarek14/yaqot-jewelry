<?php

namespace Tests\Feature\Product;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\Category\Models\Category;
use Modules\Collection\Models\Collection;
use Modules\Product\Models\Product;
use Modules\Product\Models\ProductImage;
use RuntimeException;
use Tests\TestCase;

class ProductImageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    private function imageFor(Product $product, int $sortOrder): ProductImage
    {
        $path = UploadedFile::fake()->image('photo.jpg')->store("products/{$product->id}", 'public');

        return $product->images()->create(['path' => $path, 'sort_order' => $sortOrder]);
    }

    public function test_images_are_ordered_by_sort_order_then_id(): void
    {
        $product = Product::factory()->create();
        $c = $this->imageFor($product, 2);
        $a = $this->imageFor($product, 0);
        $b = $this->imageFor($product, 0);

        $this->assertSame([$a->id, $b->id, $c->id], $product->images()->pluck('id')->all());
    }

    public function test_primary_image_is_the_lowest_sort_order_with_ties_going_to_the_oldest(): void
    {
        $product = Product::factory()->create();
        $this->imageFor($product, 5);
        $first = $this->imageFor($product, 1);
        $tied = $this->imageFor($product, 1);

        $this->assertTrue($product->primaryImage()->first()->is($first));

        $first->update(['sort_order' => 9]);

        $this->assertTrue(Product::with('primaryImage')->find($product->id)->primaryImage->is($tied));
    }

    public function test_primary_image_is_eager_loaded_per_product(): void
    {
        $products = Product::factory()->count(3)->create()->each(function (Product $product) {
            $this->imageFor($product, 1);
            $this->imageFor($product, 0);
        });

        $loaded = Product::with('primaryImage')->orderBy('id')->get();

        foreach ($loaded as $index => $product) {
            $this->assertSame($products[$index]->id, $product->primaryImage->product_id);
            $this->assertSame(0, $product->primaryImage->sort_order);
        }
    }

    public function test_a_product_without_images_has_no_primary_image(): void
    {
        $this->assertNull(Product::factory()->create()->primaryImage);
    }

    public function test_alt_text_is_the_product_name_in_the_current_locale(): void
    {
        $product = Product::factory()->create(['en' => ['name' => 'Demo Bangle'], 'ar' => ['name' => 'غويشة تجريبية']]);
        $this->imageFor($product, 0);

        app()->setLocale('ar');
        $this->assertSame('غويشة تجريبية', Product::with(['translations', 'images'])->find($product->id)->images->first()->alt);

        app()->setLocale('en');
        $this->assertSame('Demo Bangle', Product::with(['translations', 'primaryImage'])->find($product->id)->primaryImage->alt);
    }

    public function test_image_url_points_at_the_public_disk(): void
    {
        $image = $this->imageFor(Product::factory()->create(), 0);

        $this->assertSame(Storage::disk('public')->url($image->path), $image->url);
    }

    public function test_deleting_an_image_deletes_only_its_file(): void
    {
        $product = Product::factory()->create();
        $kept = $this->imageFor($product, 0);
        $deleted = $this->imageFor($product, 1);

        $deleted->delete();

        Storage::disk('public')->assertMissing($deleted->path);
        Storage::disk('public')->assertExists($kept->path);
    }

    public function test_a_rolled_back_delete_keeps_the_file(): void
    {
        $image = $this->imageFor(Product::factory()->create(), 0);

        try {
            DB::transaction(function () use ($image) {
                $image->delete();
                throw new RuntimeException('rollback');
            });
        } catch (RuntimeException) {
        }

        $this->assertModelExists($image);
        Storage::disk('public')->assertExists($image->path);
    }

    public function test_soft_deleting_a_product_keeps_its_images(): void
    {
        $product = Product::factory()->create();
        $image = $this->imageFor($product, 0);

        $product->delete();

        $this->assertModelExists($image);
        Storage::disk('public')->assertExists($image->path);
    }

    public function test_force_deleting_a_product_deletes_its_image_files_and_rows_only(): void
    {
        $product = Product::factory()->create();
        $images = [$this->imageFor($product, 0), $this->imageFor($product, 1)];
        $other = $this->imageFor(Product::factory()->create(), 0);

        $product->delete();
        $product->forceDelete();

        foreach ($images as $image) {
            $this->assertModelMissing($image);
            Storage::disk('public')->assertMissing($image->path);
        }
        $this->assertModelExists($other);
        Storage::disk('public')->assertExists($other->path);
    }

    public function test_a_rolled_back_force_delete_keeps_the_product_images_and_files(): void
    {
        $product = Product::factory()->create();
        $image = $this->imageFor($product, 0);

        try {
            DB::transaction(function () use ($product) {
                $product->forceDelete();
                throw new RuntimeException('rollback');
            });
        } catch (RuntimeException) {
        }

        $this->assertModelExists($product);
        $this->assertModelExists($image);
        Storage::disk('public')->assertExists($image->path);
    }

    public function test_a_cancelled_force_delete_keeps_the_files(): void
    {
        $product = Product::factory()->create();
        $image = $this->imageFor($product, 0);
        Product::deleting(fn () => false);

        $this->assertFalse($product->forceDelete());

        $this->assertModelExists($image);
        Storage::disk('public')->assertExists($image->path);
    }

    public function test_replacing_an_image_path_deletes_the_old_file(): void
    {
        $image = $this->imageFor(Product::factory()->create(), 0);
        $oldPath = $image->path;
        $newPath = UploadedFile::fake()->image('new.jpg')->store('products/'.$image->product_id, 'public');

        $image->update(['path' => $newPath]);

        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($newPath);
    }

    public function test_category_and_collection_images_are_cleaned_up(): void
    {
        foreach ([Category::class, Collection::class] as $model) {
            $first = UploadedFile::fake()->image('a.jpg')->store('taxonomy', 'public');
            $second = UploadedFile::fake()->image('b.jpg')->store('taxonomy', 'public');
            $record = $model::factory()->create(['image_path' => $first]);

            $record->update(['image_path' => $second]);
            Storage::disk('public')->assertMissing($first);
            Storage::disk('public')->assertExists($second);

            $record->delete();
            Storage::disk('public')->assertMissing($second);
        }
    }
}
