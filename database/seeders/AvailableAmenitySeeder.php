<?php

namespace Database\Seeders;

use App\Models\AvailableAmenity;
use Illuminate\Database\Seeder;

class AvailableAmenitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $amenities = [
            ['item_name' => 'Extra Bed / Mat', 'price' => 500.00, 'is_available' => true],
            ['item_name' => 'Extra Pillows / Blankets', 'price' => 200.00, 'is_available' => true],
            ['item_name' => 'Late Check-out Fee', 'price' => 300.00, 'is_available' => true],
            ['item_name' => 'Bottled Water', 'price' => 50.00, 'is_available' => true],
            ['item_name' => 'Laundry', 'price' => 150.00, 'is_available' => true], // Out of stock example
        ];

        foreach ($amenities as $amenity) {
            // Use firstOrCreate to prevent duplicate catalog entries on re-seed
            AvailableAmenity::firstOrCreate(
                ['item_name' => $amenity['item_name']],
                ['price' => $amenity['price'], 'is_available' => $amenity['is_available']]
            );
        }
    }
}
