<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Seeder;

class SystemSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        SystemSetting::firstOrCreate(
            ['key' => 'date_format'],
            ['value' => 'M d, Y']
        );

        SystemSetting::firstOrCreate(
            ['key' => 'time_format'],
            ['value' => 'h:i A']
        );
    }
}
