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
        Schema::create('client_type_payment_method', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->boolean('is_worker')->default(false);
            $table->foreignUuid('client_type_id')->constrained('client_types')->onDelete('cascade');
            $table->foreignUuid('payment_method_id')->constrained('payment_methods')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_type_payment_method');
    }
};
