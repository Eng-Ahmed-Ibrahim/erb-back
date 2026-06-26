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
        Schema::table('invoice_recipe', function (Blueprint $table) {
            if (!Schema::hasColumn('invoice_recipe', 'source_invoice_id')) {
                $table->uuid('source_invoice_id')
                    ->nullable()
                    ->after('invoice_id');

                $table->foreign('source_invoice_id')
                    ->references('id')
                    ->on('invoices')
                    ->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoice_recipe', function (Blueprint $table) {
            if (Schema::hasColumn('invoice_recipe', 'source_invoice_id')) {
                $table->dropForeign(['source_invoice_id']);
                $table->dropColumn('source_invoice_id');
            }
        });
    }
};






