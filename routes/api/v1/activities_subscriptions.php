<?php

use Illuminate\Support\Facades\Route;
use Modules\ActivitiesSubscriptions\UI\Http\Controllers\AcademyController;
use Modules\ActivitiesSubscriptions\UI\Http\Controllers\OfferController;
use Modules\ActivitiesSubscriptions\UI\Http\Controllers\SubscriberController;
use Modules\ActivitiesSubscriptions\UI\Http\Controllers\SubscriptionController;
use Modules\ActivitiesSubscriptions\UI\Http\Controllers\CheckInController;
use Modules\ActivitiesSubscriptions\UI\Http\Controllers\CoachController;
use Modules\ActivitiesSubscriptions\UI\Http\Controllers\FinancialReportsController;

/*
|--------------------------------------------------------------------------
| Activities Subscriptions API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for the Activities Subscriptions module.
| These routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::prefix('activities-subscriptions')->group(function () {
    
    /*
    |--------------------------------------------------------------------------
    | Academy Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('academies')->group(function () {
        Route::get('/', [AcademyController::class, 'index']);
        Route::post('/', [AcademyController::class, 'store']);
        Route::get('/{id}', [AcademyController::class, 'show']);
        Route::put('/{id}', [AcademyController::class, 'update']);
        Route::delete('/{id}', [AcademyController::class, 'destroy']);
    });

    /*
    |--------------------------------------------------------------------------
    | Coach Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('coaches')->group(function () {
        Route::get('/', [CoachController::class, 'index']);
        Route::post('/', [CoachController::class, 'store']);
        Route::get('/{id}', [CoachController::class, 'show']);
        Route::put('/{id}', [CoachController::class, 'update']);
        Route::delete('/{id}', [CoachController::class, 'destroy']);
    });

    /*
    |--------------------------------------------------------------------------
    | Offer Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('offers')->group(function () {
        Route::get('/', [OfferController::class, 'index']);
        Route::post('/', [OfferController::class, 'store']);
        Route::get('/{id}', [OfferController::class, 'show']);
        Route::put('/{id}', [OfferController::class, 'update']);
        Route::delete('/{id}', [OfferController::class, 'destroy']);
        Route::get('/academy/{academyId}', [OfferController::class, 'getByAcademy']);
    });

    /*
    |--------------------------------------------------------------------------
    | Subscriber Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('subscribers')->group(function () {
        Route::get('/', [SubscriberController::class, 'index']);
        Route::post('/', [SubscriberController::class, 'store']);
        Route::post('/search-by-identifier', [SubscriberController::class, 'searchByIdentifier']);
        Route::get('/{id}', [SubscriberController::class, 'show']);
        Route::put('/{id}', [SubscriberController::class, 'update']);
        Route::delete('/{id}', [SubscriberController::class, 'destroy']);
    });

    /*
    |--------------------------------------------------------------------------
    | Subscription Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('subscriptions')->group(function () {
        Route::get('/', [SubscriptionController::class, 'index']);
        Route::post('/', [SubscriptionController::class, 'store']);
        Route::get('/{id}', [SubscriptionController::class, 'show']);
        Route::put('/{id}', [SubscriptionController::class, 'update']);
        Route::delete('/{id}', [SubscriptionController::class, 'destroy']);
        Route::get('/subscriber/{subscriberId}', [SubscriptionController::class, 'getBySubscriber']);
        Route::get('/academy/{academyId}', [SubscriptionController::class, 'getByAcademy']);
        Route::post('/{id}/qr', [SubscriptionController::class, 'generateQR']);
        Route::get('/{id}/qr-svg', [SubscriptionController::class, 'getQRCodeSVG']);
        Route::get('/{id}/barcode-svg', [SubscriptionController::class, 'getBarcodeSVG']);
        Route::post('/verify-qr-code', [SubscriptionController::class, 'verifyQRCode']);
        Route::post('/verify-barcode', [SubscriptionController::class, 'verifyBarcode']);
        Route::post('/attendance', [SubscriptionController::class, 'recordAttendance']);
        Route::post('/verify', [SubscriptionController::class, 'verifySubscription']);
        Route::post('/generate-all-qr-codes', [SubscriptionController::class, 'generateAllQRCodes']);
    });

    /*
    |--------------------------------------------------------------------------
    | Check-in Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('check-in')->group(function () {
        Route::post('/', [CheckInController::class, 'checkIn']);
        Route::get('/attendance/{subscriptionId}', [CheckInController::class, 'getAttendanceHistory']);
        Route::get('/attendance-by-date-range', [CheckInController::class, 'getAttendanceByDateRange']);
        Route::get('/stats', [CheckInController::class, 'getAttendanceStats']);
    });

    /*
    |--------------------------------------------------------------------------
    | Financial Reports Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('financial-reports')->group(function () {
        Route::get('/dashboard-stats', [FinancialReportsController::class, 'getDashboardStats'])
            ->name('activities-subscriptions.financial-reports.dashboard-stats');
        Route::get('/revenue-analytics', [FinancialReportsController::class, 'getRevenueAnalytics'])
            ->name('activities-subscriptions.financial-reports.revenue-analytics');
        Route::get('/subscriptions-financials', [FinancialReportsController::class, 'getSubscriptionsFinancials'])
            ->name('activities-subscriptions.financial-reports.subscriptions-financials');
        Route::get('/revenue-by-academy', [FinancialReportsController::class, 'getRevenueByAcademy'])
            ->name('activities-subscriptions.financial-reports.revenue-by-academy');
        Route::get('/revenue-by-subscriber-type', [FinancialReportsController::class, 'getRevenueBySubscriberType'])
            ->name('activities-subscriptions.financial-reports.revenue-by-subscriber-type');
        Route::get('/reports/{reportType}', [FinancialReportsController::class, 'generateFinancialReport'])
            ->where('reportType', 'summary|subscriptions|revenue|academies')
            ->name('activities-subscriptions.financial-reports.generate');
    });
});
