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
        foreach (['bookings', 'reservations'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->string('payment_method')->nullable()->default('cash')->after('deposit');
                $table->string('ewallet_type')->nullable()->after('payment_method');
                $table->string('reference_number')->nullable()->after('ewallet_type');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (['bookings', 'reservations'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn(['payment_method', 'ewallet_type', 'reference_number']);
            });
        }
    }
};
