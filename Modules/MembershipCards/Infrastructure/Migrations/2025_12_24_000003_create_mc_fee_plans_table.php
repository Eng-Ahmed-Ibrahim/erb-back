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
        Schema::create('mc_fee_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('beneficiary_type'); // officer, spouse, child_under_21, parent_medical, etc.
            $table->string('weapon_type')->default('infantry'); // infantry, other
            $table->decimal('establishment_fee', 10, 2)->default(0);
            $table->decimal('annual_subscription_fee', 10, 2)->default(0);
            $table->decimal('issuance_fee', 10, 2)->default(0);
            $table->integer('version')->default(1);
            $table->boolean('active')->default(true);
            $table->text('description')->nullable();
            $table->json('age_range')->nullable(); // {"min": 6, "max": 10} for grandchildren categories
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['beneficiary_type']);
            $table->index(['weapon_type']);
            $table->index(['active']);
            $table->index(['version']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mc_fee_plans');
    }
};

