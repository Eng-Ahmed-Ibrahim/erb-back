<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AdditionalService;

class AdditionalServiceSeeder extends Seeder
{
    public function run()
    {
        $services = [
            [
                'name' => 'إضافة مرتبة',
                'code' => 'EXTRA_MATTRESS',
                'price' => 200,
                'description' => 'إضافة مرتبة إضافية للغرفة',
                'is_per_day' => true,
                'is_active' => true,
            ],
            [
                'name' => 'إضافة مرافق',
                'code' => 'EXTRA_PERSON',
                'price' => 250,
                'description' => 'إضافة شخص مرافق للحجز',
                'is_per_day' => true,
                'is_active' => true,
            ],
        ];

        foreach ($services as $service) {
            AdditionalService::updateOrCreate(
                ['code' => $service['code']],
                $service
            );
        }
    }
}