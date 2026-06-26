<?php

namespace Modules\MembershipCards\Infrastructure\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FeePlansSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Based on the official fee schedule effective from 1/1/2024
     * أولا: قيمة الاشتراكات لضباط المشاة وأسرهم
     * ثانيا: قيمة الاشتراكات السنوية لضباط الأسلحة الأخرى وأسرهم
     */
    public function run(): void
    {
        // Disable foreign key checks and clear existing fee plans
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('mc_fee_plans')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $now = now();

        // =====================================================
        // أولا: قيمة الاشتراكات لضباط المشاة وأسرهم
        // Infantry Officers and their families
        // =====================================================
        $infantryPlans = [
            [
                'name' => 'اشتراك سنوي للضباط',
                'beneficiary_type' => 'officer',
                'weapon_type' => 'infantry',
                'establishment_fee' => 750.00,
                'annual_subscription_fee' => 100.00,
                'issuance_fee' => 50.00,
                'description' => 'اشتراك سنوي لضباط المشاة',
                'age_range' => null,
            ],
            [
                'name' => 'اشتراك سنوي للزوجة',
                'beneficiary_type' => 'spouse',
                'weapon_type' => 'infantry',
                'establishment_fee' => 0.00,
                'annual_subscription_fee' => 100.00,
                'issuance_fee' => 50.00,
                'description' => 'اشتراك سنوي لزوجة ضابط المشاة',
                'age_range' => null,
            ],
            [
                'name' => 'اشتراك سنوي للأبناء المدرجين في البطاقة العلاجية أقل من 21 عام',
                'beneficiary_type' => 'child_under_21_medical',
                'weapon_type' => 'infantry',
                'establishment_fee' => 0.00,
                'annual_subscription_fee' => 0.00,
                'issuance_fee' => 50.00,
                'description' => 'أبناء أقل من 21 سنة ومدرجين في البطاقة العلاجية',
                'age_range' => json_encode(['min' => 0, 'max' => 20]),
            ],
            [
                'name' => 'اشتراك سنوي للوالدين المدرجين في البطاقة العلاجية',
                'beneficiary_type' => 'parent_medical',
                'weapon_type' => 'infantry',
                'establishment_fee' => 500.00,
                'annual_subscription_fee' => 200.00,
                'issuance_fee' => 50.00,
                'description' => 'الوالدين المدرجين في البطاقة العلاجية',
                'age_range' => null,
            ],
            [
                'name' => 'اشتراك سنوي للأبناء الخريجين وموجودين في البطاقة العلاجية',
                'beneficiary_type' => 'child_graduate_medical',
                'weapon_type' => 'infantry',
                'establishment_fee' => 500.00,
                'annual_subscription_fee' => 200.00,
                'issuance_fee' => 50.00,
                'description' => 'الأبناء الخريجين الموجودين في البطاقة العلاجية',
                'age_range' => null,
            ],
            [
                'name' => 'اشتراك سنوي للأبناء خارج البطاقة العلاجية للفرد',
                'beneficiary_type' => 'child_non_medical',
                'weapon_type' => 'infantry',
                'establishment_fee' => 500.00,
                'annual_subscription_fee' => 300.00,
                'issuance_fee' => 50.00,
                'description' => 'الأبناء غير المدرجين في البطاقة العلاجية',
                'age_range' => null,
            ],
            [
                'name' => 'اشتراك سنوي للوالدين الغير مدرجين في البطاقة العلاجية',
                'beneficiary_type' => 'parent_non_medical',
                'weapon_type' => 'infantry',
                'establishment_fee' => 500.00,
                'annual_subscription_fee' => 300.00,
                'issuance_fee' => 50.00,
                'description' => 'الوالدين غير المدرجين في البطاقة العلاجية',
                'age_range' => null,
            ],
            [
                'name' => 'اشتراك سنوي لأزواج الأبناء',
                'beneficiary_type' => 'child_spouse',
                'weapon_type' => 'infantry',
                'establishment_fee' => 1000.00,
                'annual_subscription_fee' => 500.00,
                'issuance_fee' => 50.00,
                'description' => 'أزواج وزوجات الأبناء',
                'age_range' => null,
            ],
            [
                'name' => 'اشتراك سنوي للأحفاد من 6 سنوات حتى 10 سنوات',
                'beneficiary_type' => 'grandchild_6_10',
                'weapon_type' => 'infantry',
                'establishment_fee' => 500.00,
                'annual_subscription_fee' => 300.00,
                'issuance_fee' => 50.00,
                'description' => 'الأحفاد في الفئة العمرية من 6 إلى 10 سنوات',
                'age_range' => json_encode(['min' => 6, 'max' => 10]),
            ],
            [
                'name' => 'اشتراك سنوي للأحفاد من 11 سنوات حتى 18 سنوات',
                'beneficiary_type' => 'grandchild_11_18',
                'weapon_type' => 'infantry',
                'establishment_fee' => 750.00,
                'annual_subscription_fee' => 500.00,
                'issuance_fee' => 50.00,
                'description' => 'الأحفاد في الفئة العمرية من 11 إلى 18 سنة',
                'age_range' => json_encode(['min' => 11, 'max' => 18]),
            ],
            [
                'name' => 'اشتراك سنوي للأحفاد من 20 سنة فأعلى',
                'beneficiary_type' => 'grandchild_20_plus',
                'weapon_type' => 'infantry',
                'establishment_fee' => 1000.00,
                'annual_subscription_fee' => 0.00,
                'issuance_fee' => 50.00,
                'description' => 'الأحفاد من 20 سنة فأكثر',
                'age_range' => json_encode(['min' => 20, 'max' => 999]),
            ],
        ];

        // =====================================================
        // ثانيا: قيمة الاشتراكات السنوية لضباط الأسلحة الأخرى وأسرهم
        // Other Weapons Officers and their families
        // =====================================================
        $otherWeaponsPlans = [
            [
                'name' => 'اشتراك سنوي للضباط بتخفيض والمتعاقدين',
                'beneficiary_type' => 'officer',
                'weapon_type' => 'other',
                'establishment_fee' => 1500.00,
                'annual_subscription_fee' => 500.00,
                'issuance_fee' => 100.00,
                'description' => 'اشتراك سنوي لضباط الأسلحة الأخرى والمتعاقدين',
                'age_range' => null,
            ],
            [
                'name' => 'اشتراك سنوي لزوجة ضابط',
                'beneficiary_type' => 'spouse',
                'weapon_type' => 'other',
                'establishment_fee' => 0.00,
                'annual_subscription_fee' => 300.00,
                'issuance_fee' => 100.00,
                'description' => 'اشتراك سنوي لزوجة ضابط الأسلحة الأخرى',
                'age_range' => null,
            ],
            [
                'name' => 'اشتراك سنوي للأبناء المدرجين في البطاقة العلاجية أقل من 21 عام',
                'beneficiary_type' => 'child_under_21_medical',
                'weapon_type' => 'other',
                'establishment_fee' => 0.00,
                'annual_subscription_fee' => 200.00,
                'issuance_fee' => 50.00,
                'description' => 'أبناء أقل من 21 سنة ومدرجين في البطاقة العلاجية',
                'age_range' => json_encode(['min' => 0, 'max' => 20]),
            ],
            [
                'name' => 'اشتراك سنوي للوالدين المدرجين في البطاقة العلاجية',
                'beneficiary_type' => 'parent_medical',
                'weapon_type' => 'other',
                'establishment_fee' => 750.00,
                'annual_subscription_fee' => 100.00,
                'issuance_fee' => 50.00,
                'description' => 'الوالدين المدرجين في البطاقة العلاجية',
                'age_range' => null,
            ],
            [
                'name' => 'اشتراك سنوي للأبناء الخريجين وموجودين في البطاقة العلاجية',
                'beneficiary_type' => 'child_graduate_medical',
                'weapon_type' => 'other',
                'establishment_fee' => 750.00,
                'annual_subscription_fee' => 100.00,
                'issuance_fee' => 50.00,
                'description' => 'الأبناء الخريجين الموجودين في البطاقة العلاجية',
                'age_range' => null,
            ],
            [
                'name' => 'اشتراك سنوي للأبناء خارج البطاقة العلاجية للفرد',
                'beneficiary_type' => 'child_non_medical',
                'weapon_type' => 'other',
                'establishment_fee' => 1000.00,
                'annual_subscription_fee' => 750.00,
                'issuance_fee' => 50.00,
                'description' => 'الأبناء غير المدرجين في البطاقة العلاجية',
                'age_range' => null,
            ],
            [
                'name' => 'اشتراك سنوي للوالدين الغير مدرجين في البطاقة العلاجية',
                'beneficiary_type' => 'parent_non_medical',
                'weapon_type' => 'other',
                'establishment_fee' => 1000.00,
                'annual_subscription_fee' => 750.00,
                'issuance_fee' => 50.00,
                'description' => 'الوالدين غير المدرجين في البطاقة العلاجية',
                'age_range' => null,
            ],
            [
                'name' => 'اشتراك سنوي لأزواج الأبناء',
                'beneficiary_type' => 'child_spouse',
                'weapon_type' => 'other',
                'establishment_fee' => 2000.00,
                'annual_subscription_fee' => 1000.00,
                'issuance_fee' => 100.00,
                'description' => 'أزواج وزوجات الأبناء',
                'age_range' => null,
            ],
            [
                'name' => 'اشتراك سنوي للأحفاد من 6 سنوات حتى 10 سنوات',
                'beneficiary_type' => 'grandchild_6_10',
                'weapon_type' => 'other',
                'establishment_fee' => 1000.00,
                'annual_subscription_fee' => 500.00,
                'issuance_fee' => 100.00,
                'description' => 'الأحفاد في الفئة العمرية من 6 إلى 10 سنوات',
                'age_range' => json_encode(['min' => 6, 'max' => 10]),
            ],
            [
                'name' => 'اشتراك سنوي للأحفاد من 11 سنوات حتى 18 سنوات',
                'beneficiary_type' => 'grandchild_11_18',
                'weapon_type' => 'other',
                'establishment_fee' => 1500.00,
                'annual_subscription_fee' => 750.00,
                'issuance_fee' => 100.00,
                'description' => 'الأحفاد في الفئة العمرية من 11 إلى 18 سنة',
                'age_range' => json_encode(['min' => 11, 'max' => 18]),
            ],
            [
                'name' => 'اشتراك سنوي للأحفاد من 20 سنة فأعلى',
                'beneficiary_type' => 'grandchild_20_plus',
                'weapon_type' => 'other',
                'establishment_fee' => 2000.00,
                'annual_subscription_fee' => 1000.00,
                'issuance_fee' => 100.00,
                'description' => 'الأحفاد من 20 سنة فأكثر',
                'age_range' => json_encode(['min' => 20, 'max' => 999]),
            ],
        ];

        // Insert all plans
        $allPlans = array_merge($infantryPlans, $otherWeaponsPlans);

        foreach ($allPlans as $plan) {
            DB::table('mc_fee_plans')->insert([
                'name' => $plan['name'],
                'beneficiary_type' => $plan['beneficiary_type'],
                'weapon_type' => $plan['weapon_type'],
                'establishment_fee' => $plan['establishment_fee'],
                'annual_subscription_fee' => $plan['annual_subscription_fee'],
                'issuance_fee' => $plan['issuance_fee'],
                'version' => 1,
                'active' => true,
                'description' => $plan['description'],
                'age_range' => $plan['age_range'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $this->command->info('Fee plans seeded successfully!');
        $this->command->info('Infantry plans: ' . count($infantryPlans));
        $this->command->info('Other weapons plans: ' . count($otherWeaponsPlans));
    }
}

