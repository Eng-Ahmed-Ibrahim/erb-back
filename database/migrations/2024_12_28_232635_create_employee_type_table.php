<?php

use App\Models\EmployeeType;
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
        Schema::create('employee_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        EmployeeType::insert([[
            'name' => 'جنود و صف ظباط',
        ], [
            'name' => 'مدنيين',
        ], [
            'name' => 'ظباط عاملين',
        ]]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_type');
    }
};
