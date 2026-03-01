<?php

namespace Database\Seeders;

use App\Models\{Device, DeviceType, Location};
use Illuminate\Database\Seeder;

class DeviceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Assumes we have run the DeviceType seeder.
        $elecMeterType = DeviceType::where('name', 'Electricity meter')->firstOrFail();
        $btSensorType = DeviceType::where('name', 'Bluetooth device')->firstOrFail();

        // Assumes we have run the Location seeder.
        $basementLocation = Location::where('name', 'Basement')->firstOrFail();
        $officeLocation = Location::where('name', 'Office')->firstOrFail();
        $labLocation = Location::where('name', 'Lab')->firstOrFail();

        Device::firstOrCreate([
            'type_id' => $elecMeterType->id,
            'name' => 'Smart electricity meter',
            'serial_number' => config('services.octopus.device_serial'),
            'mpan' => config('services.octopus.device_mpan'),
            'location_id' => $basementLocation->id,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Device::firstOrCreate([
            'type_id' => $btSensorType->id,
            'name' => 'Govee BT Office',
            'serial_number' => config('services.bluetooth.dummy_mac_01'),
            'location_id' => $officeLocation->id,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Device::firstOrCreate([
            'type_id' => $btSensorType->id,
            'name' => 'Govee BT Lab',
            'serial_number' => config('services.bluetooth.dummy_mac_02'),
            'location_id' => $labLocation->id,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
