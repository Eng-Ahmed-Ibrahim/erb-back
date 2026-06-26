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
        Schema::table('archived_incentives', function (Blueprint $table) {
            $table->double('advance')->default(0.0);
            $table->double('sim_card_deduction')->default(0.0);
            $table->double('other_deductions')->default(0.0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('archived_incentives', function (Blueprint $table) {
            //
        });
    }
};
