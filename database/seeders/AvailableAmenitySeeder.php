<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class AvailableAmenitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call(AmenitySeeder::class);
    }
}
