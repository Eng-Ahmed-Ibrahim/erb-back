<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('order_id')->constrained('orders')->onDelete('cascade');
            $table->uuid('product_id')->constrained('products')->onDelete('cascade');
            $table->integer('quantity');
            $table->enum('product_type', ['kitchen', 'department']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_products');
    }
};
