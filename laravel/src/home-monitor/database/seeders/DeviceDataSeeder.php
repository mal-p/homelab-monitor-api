<?php

namespace Database\Seeders;

use App\Models\{Device, DeviceData};
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class DeviceDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Assumes we have run the DeviceParameter seeder.
        $elecDevice = Device::with('deviceParameters')->where('serial_number', env('ELECTRICITY_DEVICE_SN'))->firstOrFail();
        $elecConsumptionParam = $elecDevice->deviceParameters[0] ?? null;

        if (is_null($elecConsumptionParam)) {
            throw new ModelNotFoundException('Missing expected electricity consumption parameter');
        }

        // Create 10 readings for electricity usage over the past 10 hours
        for ($idx = 9; $idx >= 0; $idx--) {
            DeviceData::create([
                'parameter_id' => $elecConsumptionParam->id,
                'value' => fake()->randomFloat(2, 0, 2), // Energy between 0.00-2.00 kWh
                'time' => Carbon::now()->subHours($idx)->format('Y-m-d H:i:sP'),
            ]);
        }
    }
}