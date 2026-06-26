<?php

use App\Http\Controllers\Api\V1\AuditController;
use Illuminate\Support\Facades\Route;

Route::group(['middleware' => 'auth:api'], function () {
    Route::get('/audit-logs', [AuditController::class, 'index']);
    Route::get('/audit-logs/{id}', [AuditController::class, 'show']);
    Route::get('/audit-logs/model/{modelType}/{modelId}', [AuditController::class, 'getModelAudit']);
    Route::get('/audit-logs/types', [AuditController::class, 'getModelTypes']);
    Route::get('/audit-logs/actions', [AuditController::class, 'getActions']);
});
