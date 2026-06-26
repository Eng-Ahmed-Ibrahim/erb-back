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
        Schema::create('recipe_request', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('recipe_id')->references('id')->on('recipes')->onDelete('cascade');
            $table->foreignUuid('request_id')->references('id')->on('requests')->onDelete('cascade');
            $table->integer('quantity')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recip_requests');
    }
};
