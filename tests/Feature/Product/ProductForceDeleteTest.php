<?php

namespace Tests\Feature\Product;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\Auth\Models\Admin;
use Modules\Category\Models\Category;
use Modules\Collection\Models\Collection;
use Modules\Product\Models\Product;
use Modules\Product\Models\ProductImage;
use Tests\TestCase;

/**
 * "Delete permanently" for a product in the trash: the row, its translations,
 * links and image rows go, and its image files are removed after commit.
 */
class ProductForceDeleteTest extends TestCase
{
    use RefreshDatabase;

    private function signIn(string $locale = 'en'): static
    {
        $this->refreshApplicationWithLocale($locale);

        return $this->actingAs(Admin::factory()->create(), 'admin');
    }

    /**
     * A product with two image files on the fake disk, a category and a collection.
     */
    private function productWithImages(bool $trashed = true): Product
    {
        $product = Product::factory()->create(['en' => ['name' => 'Demo Ring'], 'ar' => ['name' => 'خاتم']]);

        foreach (['a', 'b'] as $sort => $file) {
            $image = ProductImage::factory()->for($product)->create(['path' => "products/{$product->id}/$file.webp", 'sort_order' => $sort]);
            Storage::disk('public')->put($image->path, 'x');
        }

        $product->categories()->attach(Category::factory()->create());
        $product->collections()->attach(Collection::factory()->create());

        if ($trashed) {
            $product->delete();
        }

        return $product;
    }

    public function test_a_trashed_row_offers_delete_permanently_with_its_own_warning(): void
    {
        $this->signIn('ar');
        $product = $this->productWithImages();

        $this->get('/ar/admin/products?trashed=only')
            ->assertOk()
            ->assertSee('data-url="'.route('admin.products.force-destroy', $product).'"', false)
            ->assertSee('data-confirm="'.e(__('product::product.messages.force_confirm', [], 'ar')).'"', false)
            ->assertSee(e(__('admin.actions.delete_permanently_item', ['name' => 'خاتم'], 'ar')), false)
            ->assertSee('action="'.route('admin.products.restore', $product).'"', false);
    }

    public function test_a_product_that_is_not_trashed_offers_no_permanent_delete(): void
    {
        $this->signIn();
        $product = $this->productWithImages(trashed: false);

        $this->get('/en/admin/products')
            ->assertOk()
            ->assertDontSee(route('admin.products.force-destroy', $product), false);
    }

    public function test_it_deletes_the_product_its_rows_and_its_files(): void
    {
        $this->signIn();
        $product = $this->productWithImages();
        $category = $product->categories()->sole();
        $other = $this->productWithImages(trashed: false);

        $this->from('/en/admin/products?trashed=only')
            ->delete("/en/admin/products/{$product->id}/force")
            ->assertRedirect('/en/admin/products?trashed=only')
            ->assertSessionHas('success', __('product::product.messages.deleted_permanently'));

        $this->assertNull(Product::withTrashed()->find($product->id));
        $this->assertSame(0, DB::table('product_translations')->where('product_id', $product->id)->count());
        $this->assertSame(0, ProductImage::query()->where('product_id', $product->id)->count());
        $this->assertSame(0, DB::table('category_product')->where('product_id', $product->id)->count());
        $this->assertSame(0, DB::table('collection_product')->where('product_id', $product->id)->count());
        $this->assertSame([], Storage::disk('public')->files("products/{$product->id}"));

        // Nothing else is touched, and the category it blocked can now be deleted.
        $this->assertCount(2, Storage::disk('public')->files("products/{$other->id}"));
        $this->assertTrue($category->fresh()->canBeDeleted());
    }

    public function test_a_product_that_is_not_in_the_trash_cannot_be_deleted_permanently(): void
    {
        $this->signIn();
        $product = $this->productWithImages(trashed: false);

        $this->delete("/en/admin/products/{$product->id}/force")->assertNotFound();

        $this->assertNotNull($product->fresh());
        $this->assertCount(2, Storage::disk('public')->files("products/{$product->id}"));
    }

    public function test_guests_and_web_users_cannot_delete_permanently(): void
    {
        $this->refreshApplicationWithLocale('en');
        $product = $this->productWithImages();

        $this->delete("/en/admin/products/{$product->id}/force")
            ->assertRedirect('http://localhost/en/admin/login');

        $this->actingAs(User::factory()->create())
            ->delete("/en/admin/products/{$product->id}/force")
            ->assertRedirect('http://localhost/en/admin/login');

        $this->assertNotNull(Product::withTrashed()->find($product->id));
        $this->assertCount(2, Storage::disk('public')->files("products/{$product->id}"));
    }

    public function test_only_a_delete_request_deletes_permanently(): void
    {
        $this->signIn();
        $product = $this->productWithImages();

        $this->get("/en/admin/products/{$product->id}/force")->assertMethodNotAllowed();
        $this->assertNotNull(Product::withTrashed()->find($product->id));
    }
}
