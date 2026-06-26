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
        Schema::table('visitors', function (Blueprint $table) {
            // Add new client_type_id field
            $table->foreignUuid('client_type_id')->nullable()->after('name');
            $table->foreign('client_type_id')->references('id')->on('client_types')->onDelete('set null');

            // Add phone and emergency_contact fields
            $table->string('phone', 20)->nullable()->after('nationality');
            $table->string('emergency_contact')->nullable()->after('phone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('visitors', function (Blueprint $table) {
            // Drop foreign key and column
            $table->dropForeign(['client_type_id']);
            $table->dropColumn(['client_type_id', 'phone', 'emergency_contact']);
        });
    }
};