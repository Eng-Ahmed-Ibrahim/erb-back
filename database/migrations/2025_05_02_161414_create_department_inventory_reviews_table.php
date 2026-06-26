<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('department_inventory_reviews', function (Blueprint $table) {
            $table->uuid('id');
            $table->foreignUuid('department_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('cashier_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('waiter_id')->constrained('waiters')->onDelete('cascade');
            $table->foreignUuid('invoice_id')->constrained('invoices')->onDelete('cascade');
            $table->text('discrepancy_note')->nullable();
            $table->decimal('total_missing_quantity', 10, 2)->default(0);
            $table->decimal('estimated_loss_amount', 12, 2)->default(0);
            $table->foreignUuid('reviewed_by')->constrained('users')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->enum('status', ['pending', 'resolved', 'dismissed'])->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('department_inventory_reviews');
    }
};
