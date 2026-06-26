<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            [
                'key' => 'inventory.use_ledger_system',
                'value' => '0', // Default to old system (false)
                'type' => 'boolean',
                'group' => 'inventory',
                'description' => 'Enable the new inventory ledger system. When enabled, all inventory movements will be tracked in the ledger table for better auditing and reconciliation.',
            ],
            [
                'key' => 'inventory.ledger_start_date',
                'value' => now()->toDateString(),
                'type' => 'string',
                'group' => 'inventory',
                'description' => 'The date when the ledger system was activated. Used for migration and historical data handling.',
            ],
            [
                'key' => 'system.app_name',
                'value' => 'ERB System',
                'type' => 'string',
                'group' => 'general',
                'description' => 'Application name displayed in the system',
            ],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }

        $this->command->info('Settings seeded successfully!');
    }
}


