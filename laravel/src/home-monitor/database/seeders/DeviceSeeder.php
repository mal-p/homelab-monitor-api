<?php

namespace Database\Seeders;

use App\Models\{Device, DeviceType};
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

        Device::firstOrCreate([
            'type_id' => $elecMeterType->id,
            'name' => 'Smart electricity meter',
            'serial_number' => env('ELECTRICITY_DEVICE_SN'),
            'mpan' => env('ELECTRICITY_DEVICE_MPAN'),
            'location' => 'Home',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Device::firstOrCreate([
            'type_id' => $btSensorType->id,
            'name' => 'Govee BT Living Room',
            'serial_number' => env('BLUETOOTH_DEVICE_01_MAC'),
            'location' => 'Living Room',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Device::firstOrCreate([
            'type_id' => $btSensorType->id,
            'name' => 'Govee BT Bedroom',
            'serial_number' => env('BLUETOOTH_DEVICE_02_MAC'),
            'location' => 'Bedroom',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
