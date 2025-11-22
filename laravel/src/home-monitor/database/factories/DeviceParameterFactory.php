<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\{Device, DeviceParameter};

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DeviceParameter>
 */
class DeviceParameterFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $parameter = fake()->randomElement([
            ['Light level', 'Lumens'],
            ['Energy', 'kWh'],
            ['Window open count', ''],
        ]);

        $paramName = $parameter[0];
        $paramUnit = $parameter[1];

        return [
            'device_id' => Device::factory(),
            'name' => $paramName,
            'unit' => $paramUnit,
            'alarm_type' => fake()->randomElement(DeviceParameter::ALARM_TYPES),
            'alarm_trigger' => fake()->randomFloat(1, 25, 30),
            'alarm_hysteresis' => 0.0,
            'alarm_active' => false,
            'alarm_updated_at' => now(),
        ];
    }
}
