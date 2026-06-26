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
        // Update the status enum to include 'confirmed'
        \DB::statement("ALTER TABLE bookings MODIFY COLUMN status ENUM('pending', 'confirmed', 'active', 'completed', 'cancelled') DEFAULT 'confirmed'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to previous enum values
        \DB::statement("ALTER TABLE bookings MODIFY COLUMN status ENUM('pending', 'active', 'completed', 'cancelled') DEFAULT 'active'");
    }
};