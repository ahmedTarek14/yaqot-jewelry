<?php

namespace Modules\Option\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Option\Models\ProductOption;

/**
 * Obviously fake demo data. The real gold colours and stone types come from the data
 * migration (the owner's current list), then from the admin.
 *
 * @extends Factory<ProductOption>
 */
class ProductOptionFactory extends Factory
{
    protected $model = ProductOption::class;

    public function definition(): array
    {
        $number = fake()->unique()->numberBetween(1, 999999);

        return [
            'group' => ProductOption::GOLD_COLOR,
            'is_active' => true,
            'sort_order' => 0,
            'en' => ['name' => "Demo Option $number"],
            'ar' => ['name' => "خيار تجريبي $number"],
        ];
    }

    public function goldColor(): static
    {
        return $this->state(fn () => ['group' => ProductOption::GOLD_COLOR]);
    }

    public function stoneType(): static
    {
        return $this->state(fn () => ['group' => ProductOption::STONE_TYPE]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
