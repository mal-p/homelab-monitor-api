<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\{DeviceType, Location};

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

    /**
     * Attach a location.
     *
     * @param  Location|int|string|null  $location  Location model, id, or name
     *
     * Usage:
     *   Device::factory()->isLocated('Kitchen')->create();
     *   Device::factory()->isLocated($locationModel)->create();
     * Note:
     *   Device::factory()->isLocated('Kitchen')->make(); will persist a Location to DB
     */
    public function isLocated(Location|int|string|null $location = null): static
    {
        return $this->state(fn () => match (true) {
            $location instanceof Location => ['location_id' => $location->getKey()],
            is_int($location)             => ['location_id' => $location],
            is_string($location)          => [
                'location_id' => Location::firstOrCreate(
                    ['name' => $location],
                    ['description' => null],
                )->getKey(),
            ],
            default                       => ['location_id' => Location::factory()],
        });
    }
}
