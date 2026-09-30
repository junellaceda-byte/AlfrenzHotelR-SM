<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Room;

class RoomSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        for ($i = 1; $i <= 20; $i++) {
            Room::create([
                'room_number' => (string)$i,
                'short_stay_rate' => 600.00,
                'overnight_rate' => 2200.00, 
                'status' => 'available'
            ]);
        }
    }
}
