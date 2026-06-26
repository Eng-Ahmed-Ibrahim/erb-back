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
        Schema::create('deleted_orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->string('code')->unique();
            $table->double('tax')->nullable();
            $table->dateTime('order_date');
            $table->foreignUuid('department_id')->constrained('departments')->onDelete('cascade');
            $table->foreignUuid('order_id')->constrained('orders')->onDelete('cascade');
            $table->text('comment')->nullable();
            $table->text('deletion_note')->nullable();
            $table->foreignUuid('deleted_by')->nullable()->constrained('users')->onDelete('cascade');
            $table->enum('deleviery_type', ['kitchen', 'room']);
            $table->integer('table_number')->nullable();
            $table->boolean('to_kitchen')->default(false);
            $table->foreignUuid('client_id')->nullable()->constrained('clients')->onDelete('cascade');
            $table->foreignUuid('payment_method_id')->nullable()->constrained('payment_methods')->onDelete('cascade');
            $table->foreignUuid('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignUuid('discount_reason_id')->constrained('discount_reasons')->onDelete('cascade')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deleted_orders');
    }
};
