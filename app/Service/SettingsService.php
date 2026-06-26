<?php

namespace App\Service;

use App\Models\Setting;
use Illuminate\Support\Facades\Log;

class SettingsService
{
    /**
     * Check if inventory ledger system is enabled
     * 
     * @return bool
     */
    public static function isInventoryLedgerEnabled(): bool
    {
        return Setting::get('inventory.use_ledger_system', false);
    }

    /**
     * Enable inventory ledger system
     * 
     * @return void
     */
    public static function enableInventoryLedger(): void
    {
        Setting::set(
            'inventory.use_ledger_system',
            true,
            'boolean',
            'inventory',
            'Enable the new inventory ledger system'
        );

        // Set the start date if not already set
        if (!Setting::has('inventory.ledger_start_date')) {
            Setting::set(
                'inventory.ledger_start_date',
                now()->toDateString(),
                'string',
                'inventory',
                'The date when the ledger system was activated'
            );
        }

        Log::info('Inventory ledger system enabled', [
            'enabled_by' => auth('api')->id(),
            'enabled_at' => now(),
        ]);
    }

    /**
     * Disable inventory ledger system
     * 
     * @return void
     */
    public static function disableInventoryLedger(): void
    {
        Setting::set(
            'inventory.use_ledger_system',
            false,
            'boolean',
            'inventory',
            'Enable the new inventory ledger system'
        );

        Log::warning('Inventory ledger system disabled', [
            'disabled_by' => auth('api')->id(),
            'disabled_at' => now(),
        ]);
    }

    /**
     * Get ledger start date
     * 
     * @return string|null
     */
    public static function getLedgerStartDate(): ?string
    {
        return Setting::get('inventory.ledger_start_date');
    }

    /**
     * Get all inventory settings
     * 
     * @return array
     */
    public static function getInventorySettings(): array
    {
        return [
            'use_ledger_system' => self::isInventoryLedgerEnabled(),
            'ledger_start_date' => self::getLedgerStartDate(),
        ];
    }

    /**
     * Get setting by key
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get(string $key, $default = null)
    {
        return Setting::get($key, $default);
    }

    /**
     * Set a setting
     * 
     * @param string $key
     * @param mixed $value
     * @param string $type
     * @param string $group
     * @param string|null $description
     * @return Setting
     */
    public static function set(
        string $key,
        $value,
        string $type = 'string',
        string $group = 'general',
        ?string $description = null
    ) {
        return Setting::set($key, $value, $type, $group, $description);
    }
}


