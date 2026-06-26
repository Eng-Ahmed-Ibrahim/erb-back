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
        Schema::table('client_types', function (Blueprint $table) {
            $table->string('preferred_currency')->nullable()->after('one_order_per_day')->comment('Preferred currency code (e.g., UAD, USD, EUR)');
            $table->string('preferred_currency_name_ar')->nullable()->after('preferred_currency')->comment('Arabic name for the currency (e.g., درهم إماراتي)');
            $table->decimal('currency_divisor', 10, 4)->nullable()->after('preferred_currency_name_ar')->comment('Divisor for currency conversion (EGP_price / divisor = foreign_price)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('client_types', function (Blueprint $table) {
            $table->dropColumn(['preferred_currency', 'preferred_currency_name_ar', 'currency_divisor']);
        });
    }
};
