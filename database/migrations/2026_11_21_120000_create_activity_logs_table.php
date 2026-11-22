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
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->string('activity_type'); // payment, cancellation, overdue, login, booking
            $table->string('action'); // payment_processed, reservation_cancelled, booking_cancelled, booking_overdue, receptionist_login, booking_created, reservation_created
            $table->nullableMorphs('subject'); // subject_type, subject_id
            $table->string('guest_name')->nullable();
            $table->string('room_number')->nullable();
            $table->string('booking_type')->nullable(); // short_stay, overnight, reservation, immediate
            $table->decimal('amount', 10, 2)->nullable();
            $table->string('payment_method')->nullable();
            $table->string('reference_number')->nullable();
            $table->text('description')->nullable();
            $table->json('details')->nullable();
            $table->timestamps();

            $table->index('activity_type');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
