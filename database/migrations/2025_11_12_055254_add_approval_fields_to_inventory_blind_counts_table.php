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
        Schema::table('inventory_blind_counts', function (Blueprint $table) {
            // $table->foreignUuid('approved_by')->nullable()->after('status')->constrained('users')->nullOnDelete();
            // $table->timestamp('approved_at')->nullable()->after('approved_by');
            // $table->foreignUuid('invoice_id')->nullable()->after('approved_at')->constrained('invoices')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_blind_counts', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropForeign(['invoice_id']);
            $table->dropColumn(['approved_by', 'approved_at', 'invoice_id']);
        });
    }
};
