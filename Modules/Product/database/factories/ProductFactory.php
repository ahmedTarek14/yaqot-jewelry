<?php

namespace Modules\Product\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Option\Models\ProductOption;
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
            'type' => Product::JEWELRY,
            'brand_id' => null,
            'karat' => fake()->randomElement(config('catalog.karats')),
            // Options are owner data: tests and the seeder pass them with withGoldColor() / withStoneType().
            'gold_color_id' => null,
            'stone_type_id' => null,
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

    public function withGoldColor(ProductOption $option): static
    {
        return $this->state(fn () => ['gold_color_id' => $option->id]);
    }

    public function withStoneType(ProductOption $option): static
    {
        return $this->state(fn () => ['stone_type_id' => $option->id]);
    }

    /**
     * A gold bar: no gold colour or stones, "DEMO-BAR-" SKU, a fake bar weight.
     */
    public function bar(): static
    {
        return $this->bullion('bar', 'Demo Gold Bar', 'سبيكة ذهب تجريبية', [1, 5, 10, 20, 50, 100]);
    }

    /**
     * A gold coin: same rules as a bar.
     */
    public function coin(): static
    {
        return $this->bullion('coin', 'Demo Gold Coin', 'جنيه ذهب تجريبي', [2, 4, 8]);
    }

    /**
     * @param  array<int, int>  $weights
     */
    private function bullion(string $type, string $en, string $ar, array $weights): static
    {
        return $this->state(function () use ($type, $en, $ar, $weights) {
            $number = fake()->unique()->numberBetween(1, 999999);

            return [
                'type' => $type,
                'sku' => sprintf('DEMO-%s-%06d', strtoupper($type), $number),
                'karat' => fake()->randomElement([21, 24]),
                'gold_color_id' => null,
                'stone_type_id' => null,
                'weight_grams' => fake()->randomElement($weights),
                'en' => ['name' => "$en $number", 'description' => 'Demo description for development only.'],
                'ar' => ['name' => "$ar $number", 'description' => 'وصف تجريبي لأغراض التطوير فقط.'],
            ];
        });
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
