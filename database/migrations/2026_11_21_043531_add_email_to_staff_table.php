<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('staff', function (Blueprint $table) {
            if (! Schema::hasColumn('staff', 'email')) {
                $table->string('email')->nullable()->after('last_name');
            }
        });

        // Copy existing gmail values to email if present
        if (Schema::hasColumn('staff', 'gmail') && Schema::hasColumn('staff', 'email')) {
            DB::table('staff')
                ->whereNull('email')
                ->whereNotNull('gmail')
                ->update(['email' => DB::raw('gmail')]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('staff', function (Blueprint $table) {
            if (Schema::hasColumn('staff', 'email')) {
                $table->dropColumn('email');
            }
        });
    }
};
