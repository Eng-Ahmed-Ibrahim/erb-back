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
        Schema::create('inventory_ledger', function (Blueprint $table) {
            $table->uuid('id')->primary();
            
            // WHAT changed
            $table->foreignUuid('recipe_id')->constrained('recipes')->onDelete('restrict');
            $table->foreignUuid('department_id')->constrained('departments')->onDelete('restrict');
            
            // HOW it changed
            $table->enum('entry_type', ['debit', 'credit']);
            $table->decimal('quantity_before', 15, 3)->default(0);
            $table->decimal('quantity_after', 15, 3)->default(0);
            $table->decimal('quantity_delta', 15, 3)->default(0);
            
            // WHY it changed
            $table->enum('source_type', ['invoice', 'order', 'adjustment', 'manual', 'opening_balance']);
            $table->uuid('source_id')->nullable(); // invoice_id or order_id
            $table->enum('transaction_type', [
                'in_coming', 
                'out_going', 
                'returned', 
                'transfare', 
                'tainted', 
                'consumption', 
                'adjustment',
                'opening_balance'
            ])->nullable();
            
            // WHERE it moved (for transfers)
            $table->foreignUuid('from_department_id')->nullable()->constrained('departments')->onDelete('restrict');
            $table->foreignUuid('to_department_id')->nullable()->constrained('departments')->onDelete('restrict');
            
            // Price tracking
            $table->decimal('unit_price', 15, 3)->default(0);
            $table->decimal('total_value', 15, 3)->default(0);
            
            // Batch tracking (if from recipe_quantities)
            $table->uuid('recipe_quantity_id')->nullable();
            $table->date('expire_date')->nullable();
            
            // WHO made the change
            $table->foreignUuid('created_by')->nullable()->constrained('users')->onDelete('set null');
            
            // WHEN
            $table->dateTime('transaction_date');
            
            // Additional context
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['department_id', 'recipe_id', 'transaction_date'], 'idx_dept_recipe_date');
            $table->index(['source_type', 'source_id'], 'idx_source');
            $table->index(['created_by', 'transaction_date'], 'idx_created_by');
            $table->index('transaction_date', 'idx_transaction_date');
            $table->index('created_at', 'idx_created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_ledger');
    }
};

