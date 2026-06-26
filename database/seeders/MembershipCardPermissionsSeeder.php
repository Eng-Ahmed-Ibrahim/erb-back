<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class MembershipCardPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if permissions already exist to avoid duplicates
        $permissions = [
            // Officers
            ['name' => 'create membership card officer', 'display_name' => 'إضافة ضابط في بطاقات العضوية'],
            ['name' => 'edit membership card officer', 'display_name' => 'تعديل ضابط في بطاقات العضوية'],
            ['name' => 'delete membership card officer', 'display_name' => 'حذف ضابط من بطاقات العضوية'],

            // Beneficiaries
            ['name' => 'create membership card beneficiary', 'display_name' => 'إضافة مستفيد في بطاقات العضوية'],
            ['name' => 'edit membership card beneficiary', 'display_name' => 'تعديل مستفيد في بطاقات العضوية'],
            ['name' => 'delete membership card beneficiary', 'display_name' => 'حذف مستفيد من بطاقات العضوية'],

            // Subscriptions
            ['name' => 'create membership card subscription', 'display_name' => 'إنشاء اشتراك في بطاقات العضوية'],
            ['name' => 'delete membership card subscription', 'display_name' => 'حذف اشتراك من بطاقات العضوية'],
            ['name' => 'renew membership card subscription', 'display_name' => 'تجديد اشتراك في بطاقات العضوية'],

            // Cards
            ['name' => 'issue membership card', 'display_name' => 'إصدار بطاقة عضوية'],
            ['name' => 'issue replacement membership card', 'display_name' => 'إصدار بطاقة عضوية بديلة'],
            ['name' => 'print membership card', 'display_name' => 'طباعة بطاقة عضوية'],
            ['name' => 'encode membership card', 'display_name' => 'تشفير بطاقة عضوية'],
            ['name' => 'revoke membership card', 'display_name' => 'إلغاء بطاقة عضوية'],

            // Fee Plans
            ['name' => 'create membership card fee plan', 'display_name' => 'إنشاء خطة رسوم لبطاقات العضوية'],
            ['name' => 'edit membership card fee plan', 'display_name' => 'تعديل خطة رسوم لبطاقات العضوية'],
            ['name' => 'delete membership card fee plan', 'display_name' => 'حذف خطة رسوم من بطاقات العضوية'],
            ['name' => 'manage membership card replacement fee', 'display_name' => 'إدارة رسوم البطاقات البديلة'],
        ];

        foreach ($permissions as $permissionData) {
            // Check if permission already exists
            $existingPermission = Permission::where('name', $permissionData['name'])
                ->where('guard_name', 'api')
                ->first();

            if (!$existingPermission) {
                Permission::create([
                    'name' => $permissionData['name'],
                    'guard_name' => 'api',
                    'display_name' => $permissionData['display_name'],
                ]);
                $this->command->info("Created permission: {$permissionData['name']}");
            } else {
                $this->command->warn("Permission already exists: {$permissionData['name']}");
            }
        }

        $this->command->info('Membership card permissions seeded successfully!');
    }
}
