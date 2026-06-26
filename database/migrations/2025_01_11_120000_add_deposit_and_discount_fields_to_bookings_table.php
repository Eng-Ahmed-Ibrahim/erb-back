<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            // Deposit-related fields
            $table->decimal('deposit_amount', 10, 2)->default(0)->after('total_amount');
            $table->decimal('remaining_amount', 10, 2)->default(0)->after('deposit_amount');

            // Checkout discount fields
            $table->decimal('checkout_discount_amount', 10, 2)->default(0)->after('remaining_amount');
            $table->text('checkout_discount_reason')->nullable()->after('checkout_discount_amount');

            // Final amount after all calculations
            $table->decimal('final_amount', 10, 2)->default(0)->after('checkout_discount_reason');

            // Payment status tracking
            $table->enum('payment_status', ['pending', 'partial', 'completed'])->default('pending')->after('final_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn([
                'deposit_amount',
                'remaining_amount',
                'checkout_discount_amount',
                'checkout_discount_reason',
                'final_amount',
                'payment_status'
            ]);
        });
    }
};