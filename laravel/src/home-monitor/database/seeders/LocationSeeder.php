<?php

namespace Database\Seeders;

use App\Models\Location;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $locations = [
            'Office'   => 'Open plan office space',
            'Lab'      => 'Workbench lab space',
            'Kitchen'  => null,
            'Basement' => 'Deep underground',
        ];

        foreach ($locations as $name => $description) {
            Location::updateOrCreate(
                ['name' => $name],
                ['description' => $description],
            );
        }
    }
}
