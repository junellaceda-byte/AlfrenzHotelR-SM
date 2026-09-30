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
        Schema::create('payment_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->onDelete('cascade');
            $table->string('reference_number')->nullable()->unique(); //  Ref No.
            $table->decimal('amount_paid', 10, 2);
            $table->enum('payment_method', ['cash', 'gcash', 'credit_card', 'bank_transfer'])->default('cash');
            $table->enum('status', ['successful', 'pending', 'failed', 'refunded'])->default('successful');
            $table->timestamp('paid_at')->useCurrent();
            $table->string('processed_by')->nullable(); // Name or ID of front desk receptionist
            // $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_logs');
    }
};
