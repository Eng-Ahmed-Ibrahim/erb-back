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
        Schema::create('offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academy_id')->constrained('academies')->onDelete('cascade');
            $table->string('name');
            $table->integer('num_classes')->nullable();
            $table->integer('num_hours')->nullable();
            $table->integer('duration_days');
            $table->boolean('active')->default(true);
            $table->json('available_days');
            $table->decimal('price_infantry', 10, 2);
            $table->decimal('price_civilian', 10, 2);
            $table->decimal('price_other', 10, 2);
            $table->timestamps();
            
            $table->index(['academy_id']);
            $table->index(['active']);
            $table->index(['duration_days']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('offers');
    }
};
