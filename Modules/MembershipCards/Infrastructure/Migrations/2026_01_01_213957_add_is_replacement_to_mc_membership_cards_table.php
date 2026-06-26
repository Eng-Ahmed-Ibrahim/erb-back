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
            $table->boolean('is_replacement')->default(false)->after('notes')->comment('Whether this is a replacement card for a lost card');
            $table->index(['is_replacement']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mc_membership_cards', function (Blueprint $table) {
            $table->dropIndex(['is_replacement']);
            $table->dropColumn('is_replacement');
        });
    }
};
