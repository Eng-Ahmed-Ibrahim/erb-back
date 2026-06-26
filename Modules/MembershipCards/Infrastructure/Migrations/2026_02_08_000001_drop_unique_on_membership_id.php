<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Remove the unique constraint from membership_id since
     * duplicate membership IDs are allowed.
     */
    public function up(): void
    {
        Schema::table('mc_officers', function (Blueprint $table) {
            $table->dropUnique(['membership_id']);
            $table->dropIndex(['membership_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mc_officers', function (Blueprint $table) {
            $table->unique(['membership_id']);
            $table->index(['membership_id']);
        });
    }
};
