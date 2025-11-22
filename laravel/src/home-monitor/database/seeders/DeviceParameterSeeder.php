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
        $elecDevice = Device::where('serial_number', config('services.octopus.device_serial'))->firstOrFail();
        $btDevice01 = Device::where('serial_number', config('services.bluetooth.dummy_mac_01'))->firstOrFail();
        $btDevice02 = Device::where('serial_number', config('services.bluetooth.dummy_mac_02'))->firstOrFail();

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
