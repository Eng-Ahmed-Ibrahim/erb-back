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
        Schema::table('bookings', function (Blueprint $table) {
            $table->decimal('insurance_amount', 10, 2)->default(0)->after('total_amount');
            $table->decimal('damage_cost', 10, 2)->default(0)->after('insurance_amount');
            $table->text('damage_description')->nullable()->after('damage_cost');
            $table->boolean('insurance_refunded')->default(false)->after('damage_description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn([
                'insurance_amount',
                'damage_cost',
                'damage_description',
                'insurance_refunded'
            ]);
        });
    }
};