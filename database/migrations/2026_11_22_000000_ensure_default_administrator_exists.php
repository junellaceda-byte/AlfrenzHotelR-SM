<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! User::where('username', 'admin')->exists()) {
            User::create([
                'name' => 'Administrator',
                'username' => 'admin',
                'user_id' => 'admin',
                'email' => 'admin@hotel.com',
                'password' => Hash::make('admin123'),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Preserve admin record
    }
};
