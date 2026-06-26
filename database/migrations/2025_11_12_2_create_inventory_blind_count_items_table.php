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
        Schema::create('inventory_blind_count_items', function (Blueprint $table) {
            // $table->uuid('id')->primary();
            // $table->foreignUuid('inventory_blind_count_id')
            //     ->constrained('inventory_blind_counts')
            //     ->cascadeOnDelete();
            // $table->foreignUuid('department_store_id')
            //     ->constrained('department_store')
            //     ->cascadeOnDelete();
            // $table->foreignUuid('recipe_id')
            //     ->constrained('recipes')
            //     ->cascadeOnDelete();
            // $table->decimal('system_quantity', 12, 3)->default(0);
            // $table->decimal('actual_quantity', 12, 3)->default(0);
            // $table->decimal('variance_quantity', 12, 3)->default(0);
            // $table->enum('variance_type', ['under', 'over', 'matched'])->default('matched');
            // $table->decimal('unit_cost', 12, 4)->default(0);
            // $table->decimal('fine_amount', 14, 2)->default(0);
            // $table->text('notes')->nullable();
            // $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_blind_count_items');
    }
};



