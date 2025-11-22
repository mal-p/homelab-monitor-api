<?php

namespace Database\Seeders;

use App\Models\{Device, DeviceParameter};
use Illuminate\Database\Seeder;

class DeviceParameterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Assumes we have run the Device seeder.
        $elecDevice = Device::where('serial_number', env('ELECTRICITY_DEVICE_SN'))->firstOrFail();
        $btDevice01 = Device::where('serial_number', env('BLUETOOTH_DEVICE_01_MAC'))->firstOrFail();
        $btDevice02 = Device::where('serial_number', env('BLUETOOTH_DEVICE_02_MAC'))->firstOrFail();

        DeviceParameter::firstOrCreate([
            'device_id' => $elecDevice->id,
            'name' => 'Energy consumption',
            'unit' => 'kWh',
        ]);

        foreach([$btDevice01, $btDevice02] as $btDev) {
            DeviceParameter::firstOrCreate([
                'device_id' => $btDev->id,
                'name' => 'Temperature',
                'unit' => '°C',
            ]);

            DeviceParameter::firstOrCreate([
                'device_id' => $btDev->id,
                'name' => 'Humidity',
                'unit' => '%',
            ]);
        }
    }
}
