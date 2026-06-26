<?php

namespace Database\Seeders;

use App\Models\IncentiveType;
use Illuminate\Database\Seeder;

class IncetiveTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        IncentiveType::insert([['name' => 'الحافز الشهري'], ['name' => 'حافز شهر رمضان']]);
    }
}
