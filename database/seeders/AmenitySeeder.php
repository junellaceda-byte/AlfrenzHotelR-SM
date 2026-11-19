<?php

namespace Database\Seeders;

use App\Models\Amenity;
use Illuminate\Database\Seeder;

class AmenitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $amenities = [
            ['name' => 'Extra Bed / Mat', 'price' => 500.00, 'charged' => 'item', 'offered' => true],
            ['name' => 'Extra Pillows / Blankets', 'price' => 200.00, 'charged' => 'item', 'offered' => true],
            ['name' => 'Late Check-out Fee', 'price' => 300.00, 'charged' => 'hours', 'offered' => true],
            ['name' => 'Bottled Water', 'price' => 50.00, 'charged' => 'item', 'offered' => true],
            ['name' => 'Laundry', 'price' => 150.00, 'charged' => 'item', 'offered' => true],
        ];

        foreach ($amenities as $amenity) {
            Amenity::firstOrCreate(
                ['name' => $amenity['name']],
                [
                    'price' => $amenity['price'],
                    'charged' => $amenity['charged'],
                    'offered' => $amenity['offered'],
                ]
            );
        }
    }
}
