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
        Schema::create('bookings', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('visitor_id')->constrained()->onDelete('cascade');
            $table->foreignUlid('apartment_id')->constrained()->onDelete('cascade');
            $table->dateTime('arrival_datetime');
            $table->dateTime('checkout_datetime')->nullable();
            $table->integer('duration_days');
            $table->json('meals')->nullable(); // Array of meal types
            $table->integer('product_count')->nullable();
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->enum('status', ['active', 'completed', 'cancelled'])->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};