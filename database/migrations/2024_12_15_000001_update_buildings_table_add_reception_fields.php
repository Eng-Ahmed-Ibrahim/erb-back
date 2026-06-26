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
        Schema::table('buildings', function (Blueprint $table) {
            // Check if columns don't exist before adding them
            if (!Schema::hasColumn('buildings', 'address')) {
                $table->string('address', 500)->nullable()->after('name');
            }
            if (!Schema::hasColumn('buildings', 'floors_count')) {
                $table->integer('floors_count')->default(1)->after('address');
            }
            if (!Schema::hasColumn('buildings', 'description')) {
                $table->text('description')->nullable()->after('floors_count');
            }
            if (!Schema::hasColumn('buildings', 'color')) {
                $table->string('color', 7)->default('#1890ff')->after('description');
            }
            if (!Schema::hasColumn('buildings', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('color');
            }

            // Rename color_code to color if it exists
            if (Schema::hasColumn('buildings', 'color_code') && !Schema::hasColumn('buildings', 'color')) {
                $table->renameColumn('color_code', 'color');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('buildings', function (Blueprint $table) {
            $table->dropColumn([
                'address',
                'floors_count',
                'description',
                'is_active'
            ]);

            // Rename back to color_code
            if (Schema::hasColumn('buildings', 'color')) {
                $table->renameColumn('color', 'color_code');
            }
        });
    }
};
