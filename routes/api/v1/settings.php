<?php

use App\Http\Controllers\Api\V1\SettingsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Settings API Routes
|--------------------------------------------------------------------------
|
| Routes for managing system settings
|
*/

Route::middleware('auth:api')->group(function () {
    // Get all settings or filter by group
    Route::get('/settings', [SettingsController::class, 'index']);
    
    // Get all settings groups
    Route::get('/settings/groups', [SettingsController::class, 'groups']);
    
    // Get inventory settings
    Route::get('/settings/inventory', [SettingsController::class, 'inventory']);
    
    // Toggle inventory ledger system
    Route::post('/settings/inventory/toggle-ledger', [SettingsController::class, 'toggleInventoryLedger']);
    
    // Get specific setting
    Route::get('/settings/{key}', [SettingsController::class, 'show']);
    
    // Update specific setting
    Route::put('/settings/{key}', [SettingsController::class, 'update']);
});


