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
        Schema::table('mc_beneficiaries', function (Blueprint $table) {
            // Drop the unique constraint first
            $table->dropUnique(['officer_id', 'family_index']);
            
            // Make family_index nullable
            $table->integer('family_index')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mc_beneficiaries', function (Blueprint $table) {
            // Make family_index not nullable again
            $table->integer('family_index')->nullable(false)->change();
            
            // Re-add the unique constraint (only for non-null values)
            // Note: MySQL doesn't support partial unique indexes easily, so we'll skip this
            // If needed, you can add a unique index manually for non-null values
        });
    }
};
