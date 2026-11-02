<?php

namespace Tests\Feature\Product;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Auth\Models\Admin;
use Modules\Product\Models\Product;
use Modules\Product\Models\ProductImage;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

/**
 * Products admin: the image gallery (uploads, order tokens, removal, limits)
 * and how a failed save cleans up after itself.
 */
class ProductAdminImagesTest extends TestCase
{
    use RefreshDatabase;

    private function signIn(string $locale = 'en'): static
    {
        $this->refreshApplicationWithLocale($locale);

        return $this->actingAs(Admin::factory()->create(), 'admin');
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name_ar' => 'خاتم تجريبي',
            'name_en' => 'Demo Ring',
            'sku' => 'DEMO-RING-1',
            'type' => 'jewelry',
            'is_published' => '1',
            'is_featured' => '0',
        ], $overrides);
    }

    /**
     * A square upload; its side tells the stored files apart (all are under max_edge).
     */
    private function photo(int $side, string $name = 'photo.jpg'): UploadedFile
    {
        return UploadedFile::fake()->image($name, $side, $side);
    }

    /**
     * A saved image with a file on the faked disk.
     */
    private function savedImage(Product $product, int $sortOrder): ProductImage
    {
        $image = ProductImage::factory()->for($product)->create(['sort_order' => $sortOrder]);
        Storage::disk('public')->put($image->path, 'x');

        return $image;
    }

    /**
     * The width of each of the product's stored images, in gallery order.
     *
     * @return array<int, int>
     */
    private function storedSides(Product $product): array
    {
        return $product->images()->get()
            ->map(fn (ProductImage $image) => getimagesizefromstring(Storage::disk('public')->get($image->path))[0])
            ->all();
    }

    /* ---------------------------------------------------------------- Upload */

    public function test_store_saves_the_images_as_webp_in_the_token_order(): void
    {
        $this->signIn();

        $this->postJson('/en/admin/products', $this->payload([
            'images' => [$this->photo(900), $this->photo(1000, 'b.png'), $this->photo(1100, 'c.webp')],
            'images_order' => ['new:2', 'new:0', 'new:1'],
        ]))->assertOk();

        $product = Product::sole();
        $images = $product->images()->get();

        $this->assertSame([0, 1, 2], $images->pluck('sort_order')->all());
        $this->assertSame([1100, 900, 1000], $this->storedSides($product));
        $this->assertSame($images->first()->id, $product->primaryImage()->first()->id);

        foreach ($images as $image) {
            $this->assertMatchesRegularExpression("#^products/{$product->id}/[0-9a-f-]{36}\.webp$#", $image->path);
            $this->assertSame('image/webp', getimagesizefromstring(Storage::disk('public')->get($image->path))['mime']);
        }

        $this->assertCount(3, Storage::disk('public')->allFiles("products/{$product->id}"));
    }

    public function test_without_order_tokens_the_files_keep_their_upload_order(): void
    {
        $this->signIn();

        $this->postJson('/en/admin/products', $this->payload([
            'images' => [$this->photo(900), $this->photo(1000)],
        ]))->assertOk();

        $this->assertSame([900, 1000], $this->storedSides(Product::sole()));
    }

    public function test_a_large_image_is_scaled_down_to_the_max_edge(): void
    {
        $this->signIn();

        $this->postJson('/en/admin/products', $this->payload([
            'images' => [UploadedFile::fake()->image('big.jpg', 2400, 1800)],
        ]))->assertOk();

        $path = ProductImage::sole()->path;
        $this->assertSame([1600, 1200], array_slice(getimagesizefromstring(Storage::disk('public')->get($path)), 0, 2));
    }

    public function test_every_accepted_type_is_stored(): void
    {
        $this->signIn();

        $this->postJson('/en/admin/products', $this->payload([
            'images' => [$this->photo(800, 'a.jpg'), $this->photo(800, 'b.jpeg'), $this->photo(800, 'c.png'), $this->photo(800, 'd.webp')],
        ]))->assertOk();

        $this->assertSame(4, ProductImage::count());
    }

    /** @return array<string, array{\Closure(): UploadedFile}> */
    public static function invalidImages(): array
    {
        return [
            'gif' => [fn () => UploadedFile::fake()->image('anim.gif', 900, 900)],
            'pdf' => [fn () => UploadedFile::fake()->create('x.pdf', 10, 'application/pdf')],
            'text renamed to jpg' => [fn () => UploadedFile::fake()->createWithContent('fake.jpg', 'not an image')],
            'over 5 MB' => [fn () => UploadedFile::fake()->image('huge.jpg', 900, 900)->size(5121)],
            'under 800 px wide' => [fn () => UploadedFile::fake()->image('narrow.png', 799, 900)],
            'under 800 px tall' => [fn () => UploadedFile::fake()->image('short.png', 900, 799)],
        ];
    }

    #[DataProvider('invalidImages')]
    public function test_invalid_images_are_rejected_and_nothing_is_stored(\Closure $file): void
    {
        $this->signIn();

        $this->postJson('/en/admin/products', $this->payload(['images' => [$this->photo(900), $file()]]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['images.1'])
            ->assertJsonMissingValidationErrors(['images.0']);

        $this->assertSame(0, Product::count());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    #[DataProvider('locales')]
    public function test_upload_errors_name_the_new_image_by_position(string $locale): void
    {
        $this->signIn($locale);

        $response = $this->postJson("/$locale/admin/products", $this->payload([
            'images' => [$this->photo(900), UploadedFile::fake()->image('tiny.png', 100, 100)],
        ]))->assertUnprocessable();

        $label = str_replace(':position', '2', __('product::product.fields.new_image', [], $locale));
        $this->assertStringContainsString($label, $response->json('errors')['images.1'][0]);
    }

    /** @return array<string, array{string}> */
    public static function locales(): array
    {
        return ['arabic' => ['ar'], 'english' => ['en']];
    }

    /* --------------------------------------------------- Reorder and remove */

    public function test_saved_and_new_images_are_reordered_with_mixed_tokens(): void
    {
        $this->signIn();
        $product = Product::factory()->create();
        $first = $this->savedImage($product, 0);
        $second = $this->savedImage($product, 1);

        $this->putJson("/en/admin/products/{$product->id}", $this->payload([
            'sku' => $product->sku,
            'images' => [$this->photo(900)],
            'images_order' => ["id:{$second->id}", 'new:0', "id:{$first->id}"],
        ]))->assertOk();

        $images = $product->images()->get();
        $this->assertSame([$second->id, $images[1]->id, $first->id], $images->modelKeys());
        $this->assertSame([0, 1, 2], $images->pluck('sort_order')->all());
        $this->assertSame(900, getimagesizefromstring(Storage::disk('public')->get($images[1]->path))[0]);
        Storage::disk('public')->assertExists([$first->path, $second->path, $images[1]->path]);
    }

    public function test_unknown_tokens_are_ignored_and_unmentioned_images_follow(): void
    {
        $this->signIn();
        $product = Product::factory()->create();
        $first = $this->savedImage($product, 0);
        $second = $this->savedImage($product, 1);
        $other = $this->savedImage(Product::factory()->create(), 0);

        $this->putJson("/en/admin/products/{$product->id}", $this->payload([
            'sku' => $product->sku,
            'images' => [$this->photo(900), $this->photo(1000)],
            'images_order' => ["id:{$other->id}", 'new:7', 'new:1', "id:{$second->id}", 'id:999'],
        ]))->assertOk();

        $images = $product->images()->get();
        $this->assertCount(4, $images);
        // new:1, id:second, then the unmentioned saved image, then the unmentioned file.
        $this->assertSame($second->id, $images[1]->id);
        $this->assertSame($first->id, $images[2]->id);
        $this->assertSame(1000, getimagesizefromstring(Storage::disk('public')->get($images[0]->path))[0]);
        $this->assertSame(900, getimagesizefromstring(Storage::disk('public')->get($images[3]->path))[0]);
        $this->assertSame($other->product_id, $other->fresh()->product_id);
    }

    public function test_malformed_or_duplicate_tokens_are_rejected(): void
    {
        $this->signIn();
        $product = Product::factory()->create();
        $image = $this->savedImage($product, 0);

        $this->putJson("/en/admin/products/{$product->id}", $this->payload([
            'sku' => $product->sku,
            'images_order' => ['../etc/passwd', "id:{$image->id}", "id:{$image->id}"],
        ]))->assertUnprocessable()->assertJsonValidationErrors(['images_order.0', 'images_order.1', 'images_order.2']);
    }

    public function test_removed_images_lose_their_row_and_file(): void
    {
        $this->signIn();
        $product = Product::factory()->create();
        $removed = $this->savedImage($product, 0);
        $kept = $this->savedImage($product, 1);

        $this->putJson("/en/admin/products/{$product->id}", $this->payload([
            'sku' => $product->sku,
            'images_order' => ["id:{$removed->id}", "id:{$kept->id}"],
            'remove_images' => [$removed->id],
        ]))->assertOk();

        $this->assertModelMissing($removed);
        Storage::disk('public')->assertMissing($removed->path);
        Storage::disk('public')->assertExists($kept->path);
        $this->assertSame(0, $kept->fresh()->sort_order);
        $this->assertSame($kept->id, $product->primaryImage()->first()->id);
    }

    public function test_another_products_image_cannot_be_removed(): void
    {
        $this->signIn();
        $product = Product::factory()->create();
        $foreign = $this->savedImage(Product::factory()->create(), 0);

        $this->putJson("/en/admin/products/{$product->id}", $this->payload([
            'sku' => $product->sku,
            'remove_images' => [$foreign->id],
        ]))->assertUnprocessable()->assertJsonValidationErrors(['remove_images.0']);

        $this->assertModelExists($foreign);
        Storage::disk('public')->assertExists($foreign->path);
    }

    public function test_nothing_can_be_removed_on_create(): void
    {
        $this->signIn();
        $foreign = $this->savedImage(Product::factory()->create(), 0);

        $this->postJson('/en/admin/products', $this->payload(['remove_images' => [$foreign->id]]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['remove_images.0']);

        $this->assertModelExists($foreign);
    }

    /* ---------------------------------------------------------------- Limits */

    public function test_the_image_limit_counts_saved_removed_and_new_images(): void
    {
        $this->signIn();
        config(['catalog.images.max_per_product' => 3]);
        $product = Product::factory()->create();
        $first = $this->savedImage($product, 0);
        $this->savedImage($product, 1);

        $this->putJson("/en/admin/products/{$product->id}", $this->payload([
            'sku' => $product->sku,
            'images' => [$this->photo(800), $this->photo(800)],
        ]))->assertUnprocessable()
            ->assertJsonValidationErrors(['images'])
            ->assertJsonPath('errors.images.0', __('product::product.validation.too_many_images', ['max' => 3, 'count' => 4]));

        $this->assertSame(2, $product->images()->count());
        $this->assertCount(2, Storage::disk('public')->allFiles());

        // Removing one makes room: 2 − 1 + 2 = 3.
        $this->putJson("/en/admin/products/{$product->id}", $this->payload([
            'sku' => $product->sku,
            'images' => [$this->photo(800), $this->photo(800)],
            'remove_images' => [$first->id],
        ]))->assertOk();

        $this->assertSame(3, $product->images()->count());
    }

    public function test_the_image_limit_applies_on_create(): void
    {
        $this->signIn();
        config(['catalog.images.max_per_product' => 2]);

        $this->postJson('/en/admin/products', $this->payload([
            'images' => [$this->photo(800), $this->photo(800), $this->photo(800)],
        ]))->assertUnprocessable()->assertJsonValidationErrors(['images']);

        $this->assertSame(0, Product::count());
    }

    /* --------------------------------------------------------------- Failure */

    public function test_a_failure_mid_save_leaves_no_new_files_and_no_changes(): void
    {
        $this->signIn();
        $product = Product::factory()->create();
        $kept = $this->savedImage($product, 0);
        $created = 0;
        ProductImage::creating(function () use (&$created) {
            if (++$created === 2) {
                throw new RuntimeException('Simulated failure.');
            }
        });

        $this->putJson("/en/admin/products/{$product->id}", $this->payload([
            'sku' => 'CHANGED-1',
            'images' => [$this->photo(900), $this->photo(1000)],
            'remove_images' => [$kept->id],
        ]))->assertServerError();

        // Rolled back: the removed image and its file are still there, the new files are gone.
        $this->assertNotSame('CHANGED-1', $product->fresh()->sku);
        $this->assertModelExists($kept);
        $this->assertSame([$kept->path], Storage::disk('public')->allFiles());
    }

    public function test_a_failure_while_creating_leaves_nothing_behind(): void
    {
        $this->signIn();
        ProductImage::creating(fn () => throw new RuntimeException('Simulated failure.'));

        $this->postJson('/en/admin/products', $this->payload(['images' => [$this->photo(900)]]))->assertServerError();

        $this->assertSame(0, Product::withTrashed()->count());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    /* ------------------------------------------------------------ Soft delete */

    public function test_a_trashed_product_keeps_its_images_and_gets_them_back_on_restore(): void
    {
        $this->signIn();
        $product = Product::factory()->create();
        $image = $this->savedImage($product, 0);

        $this->delete("/en/admin/products/{$product->id}");
        Storage::disk('public')->assertExists($image->path);

        $this->patch("/en/admin/products/{$product->id}/restore");
        $this->assertSame([$image->id], $product->fresh()->images->modelKeys());
        Storage::disk('public')->assertExists($image->path);
    }
}
