<?php

use App\Http\Controllers\Api\V1\User\AuthController;
use App\Http\Controllers\Api\V1\User\ProfileController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::get('admin-login/{id}', [AuthController::class, 'adminLogin']);
});

Route::post('auth/logout', [AuthController::class, 'logout'])->middleware('auth:api');

Route::group(['middleware' => 'auth:api', 'prefix' => 'profile'], function () {
    Route::get('/', [ProfileController::class, 'index']);
    Route::post('update', [ProfileController::class, 'update']);
    Route::post('change-password', [ProfileController::class, 'changePassword']);
});
