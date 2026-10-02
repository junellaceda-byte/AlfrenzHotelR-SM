<?php

namespace Database\Seeders;

use App\Models\Room;
use Illuminate\Database\Seeder;

class RoomSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Room Seeder for Basic Rooms (20)
        // Since there is no Admin who will add rooms in the system for now.
        for ($i = 1; $i <= 20; $i++) {
            Room::create([
                'room_number' => (string) $i,
                'short_stay_rate' => 600.00,
                'overnight_rate' => 2200.00,
                'status' => 'available',
            ]);
        }
    }
}
