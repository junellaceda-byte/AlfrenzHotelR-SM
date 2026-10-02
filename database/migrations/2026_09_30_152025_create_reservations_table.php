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
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->string('guest_name');
            $table->foreignId('guest_id')->nullable()->constrained('guests')->onDelete('cascade');
            $table->string('phone_number');
            $table->dateTime('reservation_start_date');
            $table->dateTime('reservation_end_date');
            $table->integer('number_of_rooms');
            $table->json('selected_rooms'); // stores array of room numbers/ids
            $table->integer('adults_count')->nullable();
            $table->integer('children_count')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
