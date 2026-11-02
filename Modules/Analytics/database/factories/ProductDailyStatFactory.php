<?php

namespace Modules\Analytics\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Analytics\Models\ProductDailyStat;
use Modules\Product\Models\Product;

/**
 * @extends Factory<ProductDailyStat>
 */
class ProductDailyStatFactory extends Factory
{
    protected $model = ProductDailyStat::class;

    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'date' => now()->toDateString(),
            'views' => fake()->numberBetween(1, 50),
            'whatsapp_clicks' => fake()->numberBetween(0, 10),
        ];
    }

    public function on(string $date): static
    {
        return $this->state(fn () => ['date' => $date]);
    }

    public function counts(int $views, int $whatsappClicks = 0): static
    {
        return $this->state(fn () => ['views' => $views, 'whatsapp_clicks' => $whatsappClicks]);
    }
}
