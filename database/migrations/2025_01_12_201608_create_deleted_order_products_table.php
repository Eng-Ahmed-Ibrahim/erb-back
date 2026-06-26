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
        Schema::create('deleted_order_products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('deleted_order_id')->constrained('deleted_orders')->onDelete('cascade');
            $table->uuid('product_id')->constrained('products')->onDelete('cascade');
            $table->uuid('order_product_id')->constrained('order_products')->onDelete('cascade');
            $table->integer('quantity');
            $table->double('price');
            $table->string('deletion_note');
            $table->foreignUuid('deleted_by')->nullable()->constrained('users')->onDelete('cascade');
            $table->foreignUuid('reviewed_by')->nullable()->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deleted_order_products');
    }
};
