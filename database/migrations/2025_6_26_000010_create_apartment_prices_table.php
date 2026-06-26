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
        Schema::create('apartment_prices', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('apartment_id')->constrained()->onDelete('cascade');
            $table->foreignUlid('client_type_id')->constrained()->onDelete('cascade');
            $table->decimal('daily_rate', 10, 2);
            $table->decimal('weekly_rate', 10, 2)->nullable();
            $table->decimal('monthly_rate', 10, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            // Ensure one price per apartment per client type
            $table->unique(['apartment_id', 'client_type_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('apartment_prices');
    }
};