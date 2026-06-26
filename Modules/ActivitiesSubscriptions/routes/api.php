<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
    |--------------------------------------------------------------------------
    | API Routes
    |--------------------------------------------------------------------------
    |
    | Here is where you can register API routes for your application. These
    | routes are loaded by the RouteServiceProvider within a group which
    | is assigned the "api" middleware group. Enjoy building your API!
    |
*/

Route::middleware(['auth:sanctum'])->prefix('v1')->name('api.')->group(function () {
    Route::get('activitiessubscriptions', fn (Request $request) => $request->user())->name('activitiessubscriptions');

    // Check-in and attendance routes
    Route::prefix('activities-subscriptions')->group(function () {
        Route::post('subscriptions/attendance', [\Modules\ActivitiesSubscriptions\UI\Http\Controllers\CheckInController::class, 'checkIn']);
        Route::post('subscriptions/verify', [\Modules\ActivitiesSubscriptions\UI\Http\Controllers\CheckInController::class, 'verifySubscription']);
        Route::get('attendance/history/{subscriptionId}', [\Modules\ActivitiesSubscriptions\UI\Http\Controllers\CheckInController::class, 'getAttendanceHistory']);
        Route::get('attendance/date-range', [\Modules\ActivitiesSubscriptions\UI\Http\Controllers\CheckInController::class, 'getAttendanceByDateRange']);
        Route::get('attendance/stats', [\Modules\ActivitiesSubscriptions\UI\Http\Controllers\CheckInController::class, 'getAttendanceStats']);
    });
});
