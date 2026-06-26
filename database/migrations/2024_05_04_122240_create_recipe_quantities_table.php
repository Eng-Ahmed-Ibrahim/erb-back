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
        // convert all unsignedBigInteger to uuid
        Schema::create('recipe_quantities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->integer('order')->default(0);
            $table->foreignUuid('department_store_id')->constrained('department_store')->onDelete('cascade');
            $table->integer('quantity')->default(0);
            $table->double('remaining')->default(0);
            $table->double('price')->default(0);
            $table->string('expire_date');
            $table->foreignUuid('invoice_id')->nullable()->constrained('invoices')->onDelete('cascade');
            $table->foreignUuid('recipe_id')->constrained('recipes')->onDelete('cascade');
            $table->double('total_price')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recipe_quantities');
    }
};
