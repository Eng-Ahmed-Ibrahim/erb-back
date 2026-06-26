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
        Schema::create('recipe_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('department_id')->constrained()->onDelete('cascade');
            $table->date('date');
            $table->foreignId('balance_id')->constrained()->onDelete('cascade');
            $table->integer('quantity')->default(0);
            $table->foreignUuid('recipe_id')->constrained()->onDelete('cascade');
            $table->double('total_price')->default(0);
            $table->index('date');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recipe_balances');
    }
};
