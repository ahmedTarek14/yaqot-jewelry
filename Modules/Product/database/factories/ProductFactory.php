<?php

namespace Modules\Product\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Product\Models\Product;
use Modules\Product\Models\ProductImage;

/**
 * Obviously fake demo data: "Demo Piece" names and DEMO- SKUs.
 *
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $number = fake()->unique()->numberBetween(1, 999999);

        return [
            'sku' => sprintf('DEMO-%06d', $number),
            'brand_id' => null,
            'karat' => fake()->randomElement(config('catalog.karats')),
            'gold_color' => fake()->randomElement(config('catalog.gold_colors')),
            'stone_type' => fake()->randomElement(config('catalog.stone_types')),
            'weight_grams' => fake()->randomFloat(3, 1, 60),
            'is_published' => false,
            'is_featured' => false,
            'en' => [
                'name' => "Demo Piece $number",
                'description' => 'Demo description for development only.',
            ],
            'ar' => [
                'name' => "قطعة تجريبية $number",
                'description' => 'وصف تجريبي لأغراض التطوير فقط.',
            ],
        ];
    }

    public function published(): static
    {
        return $this->state(fn () => ['is_published' => true]);
    }

    public function featured(): static
    {
        return $this->state(fn () => ['is_featured' => true]);
    }

    /**
     * Image rows only; tests that need files put them on a faked disk.
     */
    public function withImages(int $count = 1): static
    {
        return $this->has(
            ProductImage::factory()->count($count)->sequence(fn ($sequence) => ['sort_order' => $sequence->index]),
            'images'
        );
    }

    /**
     * Published with an image: passes the visible() scope.
     */
    public function visible(): static
    {
        return $this->published()->withImages();
    }
}
