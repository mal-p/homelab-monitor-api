<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\DeviceType;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Device>
 */
class DeviceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type_id' => DeviceType::factory(),
            'name' => fake()->randomElement(['Lab unit', 'Office unit']),
            'serial_number' => fake()->unique()->macAddress(),
            'is_active' => true,
        ];
    }
}
