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
        Schema::create('apartments', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('building_id')->constrained()->onDelete('cascade');
            $table->string('apartment_number');
            $table->enum('room_type', ['single', 'double', 'suite']);
            $table->boolean('is_occupied')->default(false);
            $table->timestamps();

            $table->unique(['building_id', 'apartment_number']); // Unique apartment number per building
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('apartments');
    }
};