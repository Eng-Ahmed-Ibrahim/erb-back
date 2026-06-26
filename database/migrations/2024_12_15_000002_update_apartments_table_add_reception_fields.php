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
        Schema::table('apartments', function (Blueprint $table) {
            // Check if columns don't exist before adding them
            if (!Schema::hasColumn('apartments', 'apartment_number')) {
                $table->string('apartment_number', 255)->nullable()->after('building_id');
            }
            if (!Schema::hasColumn('apartments', 'floor_number')) {
                $table->integer('floor_number')->default(0)->after('room_type');
            }
            if (!Schema::hasColumn('apartments', 'max_occupancy')) {
                $table->integer('max_occupancy')->default(1)->after('floor_number');
            }
            if (!Schema::hasColumn('apartments', 'daily_rate')) {
                $table->decimal('daily_rate', 10, 2)->default(0)->after('max_occupancy');
            }
            if (!Schema::hasColumn('apartments', 'amenities')) {
                $table->json('amenities')->nullable()->after('daily_rate');
            }
            if (!Schema::hasColumn('apartments', 'description')) {
                $table->text('description')->nullable()->after('amenities');
            }
            if (!Schema::hasColumn('apartments', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('is_occupied');
            }

            // Rename number to apartment_number if it exists
            if (Schema::hasColumn('apartments', 'number') && !Schema::hasColumn('apartments', 'apartment_number')) {
                $table->renameColumn('number', 'apartment_number');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('apartments', function (Blueprint $table) {
            $table->dropColumn([
                'floor_number',
                'max_occupancy',
                'daily_rate',
                'amenities',
                'description',
                'is_active'
            ]);

            // Rename back to number
            if (Schema::hasColumn('apartments', 'apartment_number')) {
                $table->renameColumn('apartment_number', 'number');
            }
        });
    }
};
