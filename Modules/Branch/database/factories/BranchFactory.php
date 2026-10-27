<?php

namespace Modules\Branch\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Branch\Models\Branch;

/**
 * Obviously fake demo data only: example.com map links and 0100000xxxx numbers.
 *
 * @extends Factory<Branch>
 */
class BranchFactory extends Factory
{
    protected $model = Branch::class;

    public function definition(): array
    {
        $n = fake()->unique()->numberBetween(1, 9999);

        return [
            'phone' => '0100000'.fake()->numerify('####'),
            'whatsapp' => '0100000'.fake()->numerify('####'),
            'map_url' => "https://maps.example.com/branch-$n",
            'is_active' => true,
            'sort_order' => 0,
            'ar' => [
                'name' => "فرع تجريبي $n",
                'address' => "عنوان تجريبي $n",
                'working_hours' => 'مواعيد تجريبية',
            ],
            'en' => [
                'name' => "Demo Branch $n",
                'address' => "Demo Address $n",
                'working_hours' => 'Demo working hours',
            ],
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    public function withoutOptional(): static
    {
        return $this->state(fn (array $attributes) => [
            'whatsapp' => null,
            'map_url' => null,
            'ar' => ['working_hours' => null] + $attributes['ar'],
            'en' => ['working_hours' => null] + $attributes['en'],
        ]);
    }
}
