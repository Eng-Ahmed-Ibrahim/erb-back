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
        Schema::create('mc_attachments', function (Blueprint $table) {
            $table->id();
            // Polymorphic relationship - can belong to officer or beneficiary
            $table->morphs('attachable'); // Creates attachable_type and attachable_id
            $table->string('original_name'); // Original file name
            $table->string('file_path'); // Stored file path
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size')->nullable(); // Size in bytes
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['attachable_type', 'attachable_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mc_attachments');
    }
};
