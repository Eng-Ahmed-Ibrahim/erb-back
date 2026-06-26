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
        Schema::table('mc_membership_cards', function (Blueprint $table) {
            $table->boolean('show_expiry_date')->default(true)->after('expiry_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mc_membership_cards', function (Blueprint $table) {
            $table->dropColumn('show_expiry_date');
        });
    }
};
