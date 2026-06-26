<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->integer('amount');
            $table->enum('type', ['in', 'out']);
            $table->enum('status', ['done', 'postpaid', 'visa', 'cash', 'unpaid']);
            $table->foreignUuid('payable_id')->nullable()->constrained('payables')->onDelete('cascade');
            $table->foreignUuid(('order_id'))->nullable()->constrained('orders')->onDelete('cascade');
            $table->text('note')->nullable();
            $table->foreignUuid('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
