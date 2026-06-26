<?php

use App\Http\Controllers\Api\V1\InventoryBlindCountController;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;

Route::prefix('store')
    ->middleware('auth:api')
    ->group(function (Router $route) {
        $route->prefix('inventory/blind-count')->group(function (Router $blindRoute) {
            $blindRoute->get('/items', [InventoryBlindCountController::class, 'listItems']);
            $blindRoute->post('/', [InventoryBlindCountController::class, 'store']);
            $blindRoute->get('/', [InventoryBlindCountController::class, 'index']);
            $blindRoute->get('/{inventoryBlindCount}', [InventoryBlindCountController::class, 'show']);
            $blindRoute->get('/{inventoryBlindCount}/download', [InventoryBlindCountController::class, 'download']);
            $blindRoute->post('/{inventoryBlindCount}/approve', [InventoryBlindCountController::class, 'approve']);
        });
    });



