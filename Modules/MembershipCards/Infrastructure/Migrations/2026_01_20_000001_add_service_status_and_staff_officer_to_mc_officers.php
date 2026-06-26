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
        Schema::table('mc_officers', function (Blueprint $table) {
            $table->enum('service_status', ['active','retired', 'transferred', 'deceased', 'martyr', 'recalled'])->nullable()->after('weapon_type');
            $table->boolean('is_staff_officer')->default(false)->after('service_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mc_officers', function (Blueprint $table) {
            $table->dropColumn(['service_status', 'is_staff_officer']);
        });
    }
};
