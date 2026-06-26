<?php

use App\Http\Controllers\Api\V1\InventoryLedgerController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->group(function () {
    
    Route::prefix('ledger')->group(function () {
        
        Route::get('/recipe/{recipe_id}', [InventoryLedgerController::class, 'getRecipeLedger']);
        
        Route::get('/source', [InventoryLedgerController::class, 'getSourceLedger']);
        
        Route::get('/balance', [InventoryLedgerController::class, 'getLedgerBalance']);
        
        Route::get('/report/detailed', [InventoryLedgerController::class, 'detailedReport']);
    });
    
    Route::prefix('reconciliation')->group(function () {
        
        Route::post('/recipe', [InventoryLedgerController::class, 'reconcileRecipe']);
        
        Route::post('/department/{department_id}', [InventoryLedgerController::class, 'reconcileDepartment']);
        
        Route::post('/system', [InventoryLedgerController::class, 'reconcileSystem']);
        
        Route::get('/discrepancies', [InventoryLedgerController::class, 'findDiscrepancies']);
    });
    
    Route::get('/inventory/health', [InventoryLedgerController::class, 'healthCheck']);
});

