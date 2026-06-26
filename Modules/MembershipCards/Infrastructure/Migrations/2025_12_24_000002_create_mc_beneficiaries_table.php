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
        Schema::create('mc_beneficiaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('officer_id')->constrained('mc_officers')->onDelete('cascade');
            $table->string('full_name');
            $table->enum('relationship_type', ['spouse', 'child', 'parent', 'grandchild', 'child_spouse']);
            $table->date('birth_date')->nullable();
            $table->string('national_id', 14)->nullable();
            $table->integer('family_index');
            $table->text('notes')->nullable();
            $table->string('photo')->nullable()->comment('Photo path for beneficiary card');
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['officer_id']);
            $table->index(['relationship_type']);
            $table->index(['national_id']);
            $table->index(['full_name']);
            
            // Unique family_index per officer
            $table->unique(['officer_id', 'family_index']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mc_beneficiaries');
    }
};

