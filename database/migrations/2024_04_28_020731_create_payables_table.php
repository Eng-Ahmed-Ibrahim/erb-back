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
        Schema::create('payables', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->integer('amount');
            $table->enum('type', ['salaries', 'incentives', 'expenses', 'invoices']);
            $table->text('note')->nullable();
            $table->string('image')->nullable();
            $table->uuid('client_id')->nullable();
            $table->foreignUuid('invoice_id')->nullable()->constrained('invoices')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payables');
    }
};
