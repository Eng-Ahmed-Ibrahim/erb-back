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
        Schema::create('mc_officers', function (Blueprint $table) {
            $table->id();
            $table->string('national_id', 14)->unique();
            $table->string('full_name');
            $table->string('rank');
            $table->enum('weapon_type', ['infantry', 'other'])->default('infantry');
            $table->string('seniority_number')->nullable();
            $table->string('membership_number', 50)->unique();
            $table->integer('age')->nullable();
            $table->text('notes')->nullable();
            $table->string('photo')->nullable()->comment('Photo path for officer card');
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['rank']);
            $table->index(['weapon_type']);
            $table->index(['full_name']);
            $table->index(['membership_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mc_officers');
    }
};

