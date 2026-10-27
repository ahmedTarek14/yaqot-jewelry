<?php

namespace Tests\Feature\Admin;

use App\Support\ImageUploader;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class ImageUploaderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    /**
     * @return array{int, int, string}
     */
    private function storedImage(string $path): array
    {
        [$width, $height, $type] = getimagesizefromstring(Storage::disk('public')->get($path));

        return [$width, $height, image_type_to_mime_type($type)];
    }

    public function test_it_stores_a_webp_under_a_server_generated_uuid_name(): void
    {
        $path = (new ImageUploader)->store(UploadedFile::fake()->image('../../evil name.png', 400, 300), 'categories');

        $this->assertMatchesRegularExpression('#^categories/[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}\.webp$#', $path);
        Storage::disk('public')->assertExists($path);
        $this->assertSame([400, 300, 'image/webp'], $this->storedImage($path));
    }

    public function test_large_images_are_scaled_down_to_the_max_edge_keeping_the_ratio(): void
    {
        $uploader = new ImageUploader;

        $landscape = $uploader->store(UploadedFile::fake()->image('wide.jpg', 3200, 2000), 'categories');
        $portrait = $uploader->store(UploadedFile::fake()->image('tall.jpg', 1000, 4000), 'categories');

        $this->assertSame([1600, 1000, 'image/webp'], $this->storedImage($landscape));
        $this->assertSame([400, 1600, 'image/webp'], $this->storedImage($portrait));
    }

    public function test_small_images_are_never_upscaled(): void
    {
        $path = (new ImageUploader)->store(UploadedFile::fake()->image('small.webp', 250, 200), 'categories');

        $this->assertSame([250, 200, 'image/webp'], $this->storedImage($path));
    }

    public function test_store_while_keeps_the_file_when_the_write_succeeds(): void
    {
        $result = (new ImageUploader)->storeWhile(UploadedFile::fake()->image('a.jpg', 300, 300), 'categories', fn (?string $path) => $path);

        Storage::disk('public')->assertExists($result);
    }

    public function test_store_while_removes_the_new_file_and_rethrows_when_the_write_fails(): void
    {
        $stored = null;

        try {
            (new ImageUploader)->storeWhile(UploadedFile::fake()->image('a.jpg', 300, 300), 'categories', function (?string $path) use (&$stored) {
                $stored = $path;

                throw new RuntimeException('write failed');
            });
            $this->fail('The exception must not be swallowed.');
        } catch (RuntimeException $e) {
            $this->assertSame('write failed', $e->getMessage());
        }

        $this->assertNotNull($stored);
        Storage::disk('public')->assertMissing($stored);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_store_while_without_a_file_passes_null(): void
    {
        $this->assertNull((new ImageUploader)->storeWhile(null, 'categories', fn (?string $path) => $path));
    }

    public function test_delete_ignores_empty_paths_and_removes_only_the_given_file(): void
    {
        $disk = Storage::disk('public');
        $disk->put('categories/keep.webp', 'x');
        $disk->put('categories/drop.webp', 'x');

        $uploader = new ImageUploader;
        $uploader->delete(null);
        $uploader->delete('');
        $uploader->delete('categories/drop.webp');

        $disk->assertMissing('categories/drop.webp');
        $disk->assertExists('categories/keep.webp');
    }

    public function test_the_rules_follow_the_catalog_config(): void
    {
        $this->assertSame([
            'image',
            'mimes:jpg,jpeg,png,webp',
            'max:5120',
            'dimensions:min_width=200,min_height=200,max_width=6000,max_height=6000',
        ], ImageUploader::rules('categories'));
    }

    public function test_the_minimum_size_depends_on_the_upload_type(): void
    {
        $this->assertSame(200, ImageUploader::minPixels('categories'));
        $this->assertSame(200, ImageUploader::minPixels('collections'));
        $this->assertSame(800, ImageUploader::minPixels('products'));
        $this->assertContains('dimensions:min_width=800,min_height=800,max_width=6000,max_height=6000', ImageUploader::rules('products'));

        config(['catalog.images.min_pixels.products' => 1000]);
        $this->assertContains('dimensions:min_width=1000,min_height=1000,max_width=6000,max_height=6000', ImageUploader::rules('products'));
    }

    public function test_an_unknown_upload_type_is_refused(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ImageUploader::rules('banners');
    }
}
