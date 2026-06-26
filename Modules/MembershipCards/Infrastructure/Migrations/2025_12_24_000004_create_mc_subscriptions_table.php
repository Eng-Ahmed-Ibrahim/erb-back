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
        Schema::create('mc_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('officer_id')->constrained('mc_officers')->onDelete('cascade');
            $table->foreignId('beneficiary_id')->nullable()->constrained('mc_beneficiaries')->onDelete('cascade');
            $table->foreignId('fee_plan_id')->constrained('mc_fee_plans')->onDelete('restrict');
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('status', ['active', 'expired', 'suspended'])->default('active');
            $table->decimal('paid_establishment_fee', 10, 2)->default(0);
            $table->decimal('paid_annual_fee', 10, 2)->default(0);
            $table->decimal('paid_issuance_fee', 10, 2)->default(0);
            $table->foreignUuid('created_by')->constrained('users')->onDelete('restrict');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['officer_id']);
            $table->index(['beneficiary_id']);
            $table->index(['fee_plan_id']);
            $table->index(['status']);
            $table->index(['start_date', 'end_date']);
            $table->index(['created_by']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mc_subscriptions');
    }
};

