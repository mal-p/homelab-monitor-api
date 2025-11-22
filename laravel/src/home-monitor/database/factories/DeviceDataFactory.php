<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\{DeviceData, DeviceParameter};

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DeviceData>
 */
class DeviceDataFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'parameter_id' => DeviceParameter::factory(),
            'time' => fake()->dateTimeBetween('-2 hour', 'now')->format(DeviceData::DATETIME_FORMAT),
            'value' => fake()->randomFloat(1, 20, 30),
        ];
    }
}
