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

        Schema::create('archived_incentives', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('job_id');
            $table->decimal('discount', 8, 2);
            $table->decimal('reward', 8, 2);
            $table->decimal('total_incentives', 8, 2);
            $table->decimal('point_value', 8, 2);
            $table->integer('points');
            $table->date('month');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('archived_incentives');
    }
};
