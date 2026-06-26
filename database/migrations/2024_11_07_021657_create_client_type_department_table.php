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
        Schema::create('client_type_department', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('client_type_id')->constrained('client_types')->onDelete('cascade');
            $table->foreignUuid('department_id')->constrained('departments')->onDelete('cascade');
            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_type_department');
    }
};
