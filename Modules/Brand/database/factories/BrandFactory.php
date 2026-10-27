<?php

namespace Modules\Brand\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Brand\Models\Brand;

/**
 * Obviously fake demo data; real brands are seeded only from the owner's list.
 *
 * @extends Factory<Brand>
 */
class BrandFactory extends Factory
{
    protected $model = Brand::class;

    public function definition(): array
    {
        $number = fake()->unique()->numberBetween(1, 9999);

        return [
            'is_active' => true,
            'sort_order' => 0,
            'en' => ['name' => "Demo Brand $number"],
            'ar' => ['name' => "علامة تجريبية $number"],
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
