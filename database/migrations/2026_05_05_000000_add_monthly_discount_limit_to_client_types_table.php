<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('client_types', function (Blueprint $table) {
            $table->decimal('monthly_discount_limit', 15, 2)->nullable()->after('discount');
        });
    }

    public function down(): void
    {
        Schema::table('client_types', function (Blueprint $table) {
            $table->dropColumn('monthly_discount_limit');
        });
    }
};
