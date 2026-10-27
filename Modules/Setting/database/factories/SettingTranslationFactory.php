<?php

namespace Modules\Setting\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Setting\Models\Setting;
use Modules\Setting\Models\SettingTranslation;

/**
 * @extends Factory<SettingTranslation>
 */
class SettingTranslationFactory extends Factory
{
    protected $model = SettingTranslation::class;

    public function definition(): array
    {
        return [
            'setting_id' => Setting::factory(),
            'locale' => 'en',
            'value' => fake()->sentence(),
        ];
    }
}
