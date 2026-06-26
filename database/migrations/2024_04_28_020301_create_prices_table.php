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
        Schema::create('prices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->double('price')->default(0);
            $table->tinyInteger('default')->default(1);
            $table->foreignUuid('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignUuid('client_type_id')->constrained('client_types')->onDelete('cascade')->nullable();
            $table->foreignUuid('client_id')->constrained('clients')->onDelete('cascade')->nullable();
            $table->double('service')->default(0);
            $table->double('profit')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prices');
    }
};
