<?php

namespace Database\Seeders;

use App\Models\MonthlyTarget;
use Illuminate\Database\Seeder;

class MonthlyTargetSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $currentYear = (int) now()->setTimezone('Asia/Manila')->format('Y');
        $currentMonth = (int) now()->setTimezone('Asia/Manila')->format('n');

        MonthlyTarget::firstOrCreate(
            [
                'year' => $currentYear,
                'month' => $currentMonth,
            ],
            [
                'target_amount' => 150000.00,
            ]
        );
    }
}
