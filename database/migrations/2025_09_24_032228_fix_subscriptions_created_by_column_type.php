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
        Schema::table('subscriptions', function (Blueprint $table) {
            // Drop the existing foreign key constraint
            $table->dropForeign(['created_by']);
        });
        
        Schema::table('subscriptions', function (Blueprint $table) {
            // Change the column type from UUID to ULID (26 chars)
            $table->string('created_by', 26)->change();
        });
        
        Schema::table('subscriptions', function (Blueprint $table) {
            // Re-add the foreign key constraint
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            // Drop the foreign key constraint
            $table->dropForeign(['created_by']);
            
            // Change back to UUID (if needed)
            $table->uuid('created_by')->change();
            
            // Re-add the foreign key constraint
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
        });
    }
};