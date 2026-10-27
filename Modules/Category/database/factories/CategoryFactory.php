<?php

namespace Modules\Category\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Category\Models\Category;

/**
 * Obviously fake demo data; real categories are seeded only from the owner's list.
 *
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        $number = fake()->unique()->numberBetween(1, 9999);

        return [
            'image_path' => null,
            'is_active' => true,
            'sort_order' => 0,
            'en' => ['name' => "Demo Category $number", 'description' => null],
            'ar' => ['name' => "فئة تجريبية $number", 'description' => null],
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
