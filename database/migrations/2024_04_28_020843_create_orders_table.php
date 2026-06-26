<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->enum('status', ['processing', 'returned', 'paid', 'completed', 'closed'])->default('processing');
            $table->string('code')->unique();
            $table->double('tax')->nullable();
            $table->dateTime('order_date');
            $table->foreignUuid('department_id')->constrained('departments')->onDelete('cascade');
            $table->text('comment')->nullable();
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

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
