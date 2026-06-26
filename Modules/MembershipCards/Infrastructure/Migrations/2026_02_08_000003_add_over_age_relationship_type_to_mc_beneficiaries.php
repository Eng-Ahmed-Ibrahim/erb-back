<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE mc_beneficiaries MODIFY COLUMN relationship_type ENUM('spouse', 'child', 'parent', 'grandchild', 'child_spouse', 'brother', 'sister', 'sister_spouse', 'over_age')");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE mc_beneficiaries MODIFY COLUMN relationship_type ENUM('spouse', 'child', 'parent', 'grandchild', 'child_spouse', 'brother', 'sister', 'sister_spouse')");
    }
};
