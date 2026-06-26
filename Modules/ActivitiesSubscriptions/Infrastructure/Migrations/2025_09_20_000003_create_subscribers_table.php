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
        Schema::create('subscribers', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->enum('type', ['infantry', 'civilian', 'other']);
            $table->string('national_id')->nullable()->unique();
            $table->string('military_id')->nullable()->unique();
            $table->string('phone')->nullable();
            $table->timestamps();
            
            $table->index(['type']);
            $table->index(['full_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscribers');
    }
};
