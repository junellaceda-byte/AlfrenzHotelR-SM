<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->string('room_number')->unique();
            $table->decimal('short_stay_rate', 8, 2)->default(600.00); // Short Stay using hourly rate
            $table->decimal('overnight_rate', 8, 2)->default(2200.00); // Overnight Stay using night rate
            $table->enum('status', ['available', 'occupied', 'under_maintenance', 'reserved'])->default('available');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
