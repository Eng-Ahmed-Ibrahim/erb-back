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
        // Schema::table('incentives', function (Blueprint $table) {
        //   $table->foreignId('type')->constrained('incentive_types')->onDelete('cascade')->default(1);
        // });

        Schema::table('incentives', function (Blueprint $table) {
            $table->unsignedBigInteger('type')->default(1);
        });

        Schema::table('incentives', function (Blueprint $table) {
            $table->foreign('type')->references('id')->on('incentive_types')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('incentives', function (Blueprint $table) {
            //
        });
    }
};
