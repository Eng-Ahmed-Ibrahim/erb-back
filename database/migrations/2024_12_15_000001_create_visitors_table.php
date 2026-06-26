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
        Schema::create('visitors', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->enum('visitor_type', ['infantry', 'weapons', 'civilian']);
            $table->string('id_type'); // national_id, passport, etc.
            $table->string('id_number');
            $table->string('nationality');
            $table->string('vehicle_number')->nullable();
            $table->string('plate_number')->nullable();
            $table->text('notes')->nullable();
            $table->string('signature_path')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visitors');
    }
};