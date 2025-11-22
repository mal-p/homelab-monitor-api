<?php

namespace Database\Seeders;

use App\Models\DeviceType;
use Illuminate\Database\Seeder;

class DeviceTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $deviceTypes = [
            'Electricity meter' => 'Smart electricity meter with Octopus integration',
            'Gas meter' => 'Smart gas meter with Octopus integration',
            'Bluetooth device' => 'Govee H5075 Bluetooth temperature/humidity',
        ];

        foreach($deviceTypes as $typeName => $typeDesc) {
            DeviceType::firstOrCreate(
                ['name' => $typeName],
                ['description' => $typeDesc],
            );
        }
    }
}
