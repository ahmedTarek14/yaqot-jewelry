<?php

namespace Tests\Feature\Product;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Product\Models\Product;
use Tests\TestCase;

class ProductVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private function visibleIds(): array
    {
        return Product::visible()->pluck('id')->all();
    }

    public function test_a_published_product_with_an_image_and_both_names_is_visible(): void
    {
        $product = Product::factory()->visible()->create();

        $this->assertSame([$product->id], $this->visibleIds());
    }

    public function test_an_unpublished_product_is_hidden(): void
    {
        Product::factory()->withImages()->create(['is_published' => false]);

        $this->assertSame([], $this->visibleIds());
    }

    public function test_a_product_without_images_is_hidden(): void
    {
        Product::factory()->published()->create();

        $this->assertSame([], $this->visibleIds());
    }

    public function test_a_product_missing_a_locale_name_is_hidden(): void
    {
        foreach (['ar', 'en'] as $missing) {
            $product = Product::factory()->visible()->create();
            $product->translations()->where('locale', $missing)->delete();

            $this->assertNotContains($product->id, $this->visibleIds(), "missing $missing translation");
        }
    }

    public function test_a_product_with_a_blank_locale_name_is_hidden(): void
    {
        foreach (['ar', 'en'] as $blank) {
            $product = Product::factory()->visible()->create();
            $product->translations()->where('locale', $blank)->update(['name' => '   ']);

            $this->assertNotContains($product->id, $this->visibleIds(), "blank $blank name");
        }
    }

    public function test_a_soft_deleted_product_is_hidden(): void
    {
        Product::factory()->visible()->create()->delete();

        $this->assertSame([], $this->visibleIds());
    }

    public function test_unpublishing_hides_and_republishing_shows(): void
    {
        $product = Product::factory()->visible()->create();

        $product->update(['is_published' => false]);
        $this->assertSame([], $this->visibleIds());

        $product->update(['is_published' => true]);
        $this->assertSame([$product->id], $this->visibleIds());
    }

    public function test_featured_and_latest_published_scopes(): void
    {
        $older = Product::factory()->visible()->featured()->create(['published_at' => now()->subDay()]);
        $newer = Product::factory()->visible()->create(['published_at' => now()]);

        $this->assertSame([$older->id], Product::visible()->featured()->pluck('id')->all());
        $this->assertSame([$newer->id, $older->id], Product::visible()->latestPublished()->pluck('id')->all());
    }
}
