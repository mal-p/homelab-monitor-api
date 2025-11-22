<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'homelab',
            'email' => 'homelab@localhost',
        ]);

        // Run application seeders
        $this->call(DeviceTypeSeeder::class);
        $this->call(DeviceSeeder::class);
        $this->call(DeviceParameterSeeder::class);

        // Run dummy data seeder
        if (env('APP_DEBUG') === true) {
            $this->call(DeviceDataSeeder::class);
        }
    }
}
