<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Renames membership_number -> military_number (الرقم العسكري)
     * Adds new membership_id column (رقم العضوية)
     */
    public function up(): void
    {
        Schema::table('mc_officers', function (Blueprint $table) {
            // Rename membership_number to military_number
            $table->renameColumn('membership_number', 'military_number');
        });

        Schema::table('mc_officers', function (Blueprint $table) {
            // Add new membership_id column (رقم العضوية) - manual entry
            $table->string('membership_id', 50)->nullable()->unique()->after('military_number');
            
            // Add index for membership_id
            $table->index(['membership_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mc_officers', function (Blueprint $table) {
            $table->dropIndex(['membership_id']);
            $table->dropColumn('membership_id');
        });

        Schema::table('mc_officers', function (Blueprint $table) {
            $table->renameColumn('military_number', 'membership_number');
        });
    }
};
