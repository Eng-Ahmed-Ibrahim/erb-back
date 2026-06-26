<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('client_types', function (Blueprint $table) {
            $table->boolean('one_order_per_day')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('client_types', function (Blueprint $table) {
            $table->dropColumn('one_order_per_day');
        });
    }
};