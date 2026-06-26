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
        // Schema::table('audits', function (Blueprint $table) {
        //     // Change auditable_id from unsignedBigInteger to string to support ULIDs (26 chars)
        //     $table->string('auditable_id', 36)->nullable()->change();
            
        //     // Also change user_id to support ULIDs
        //     $table->string('user_id', 36)->nullable()->change();
        // });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // WARNING: Cannot revert to bigint if ULIDs exist
        // If you need to rollback, truncate audits table first:
        // DB::table('audits')->truncate();
        
        // For safety, we'll keep the string type even on rollback
        // since reverting to bigint would break existing ULID data
        
        // Schema::table('audits', function (Blueprint $table) {
        //     $table->unsignedBigInteger('auditable_id')->nullable()->change();
        //     $table->unsignedBigInteger('user_id')->nullable()->change();
        // });
    }
};
