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
        Schema::create('inventory_blind_counts', function (Blueprint $table) {
            // $table->uuid('id')->primary();
            // $table->foreignUuid('department_id')->constrained('departments')->cascadeOnDelete();
            // $table->foreignUuid('cashier_id')->constrained('users')->cascadeOnDelete();
            // $table->foreignId('waiter_old_id')->constrained('waiters')->cascadeOnDelete();
            // $table->foreignId('waiter_new_id')->constrained('waiters')->cascadeOnDelete();
            // $table->timestamp('submitted_at')->nullable();
            // $table->text('notes')->nullable();
            // $table->unsignedInteger('items_count')->default(0);
            // $table->decimal('total_under_quantity', 12, 3)->default(0);
            // $table->decimal('total_over_quantity', 12, 3)->default(0);
            // $table->decimal('total_fine_amount', 14, 2)->default(0);
            // $table->string('status')->default('submitted');
            // $table->string('pdf_path')->nullable();
            // $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_blind_counts');
    }
};

