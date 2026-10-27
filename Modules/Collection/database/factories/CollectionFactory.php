<?php

namespace Modules\Collection\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Collection\Models\Collection;

/**
 * Obviously fake demo data; real collections are seeded only from the owner's list.
 *
 * @extends Factory<Collection>
 */
class CollectionFactory extends Factory
{
    protected $model = Collection::class;

    public function definition(): array
    {
        $number = fake()->unique()->numberBetween(1, 9999);

        return [
            'image_path' => null,
            'is_active' => true,
            'show_on_homepage' => false,
            'sort_order' => 0,
            'en' => ['name' => "Demo Collection $number", 'description' => null],
            'ar' => ['name' => "مجموعة تجريبية $number", 'description' => null],
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    public function onHomepage(): static
    {
        return $this->state(fn () => ['show_on_homepage' => true]);
    }
}
