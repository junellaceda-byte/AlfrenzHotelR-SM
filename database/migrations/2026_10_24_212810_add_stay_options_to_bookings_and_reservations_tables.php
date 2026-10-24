<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['bookings', 'reservations'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->unsignedTinyInteger('short_stay_hours')->nullable();
                $table->boolean('with_breakfast')->default(false);
                $table->unsignedTinyInteger('extra_beds')->default(0);
                $table->string('discount_type')->nullable();
            });
        }
    }

    public function down(): void
    {
        foreach (['bookings', 'reservations'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn(['short_stay_hours', 'with_breakfast', 'extra_beds', 'discount_type']);
            });
        }
    }
};