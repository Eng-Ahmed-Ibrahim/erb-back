<?php

use App\Http\Controllers\Api\V1\AccountingController;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;

/*
 * |--------------------------------------------------------------------------
 * | Accounting API Routes
 * |--------------------------------------------------------------------------
 * |
 * | Here is where you can register accounting related routes for your API.
 * | These routes handle financial analytics, booking tracking, staff
 * | performance, and reporting for the hotel management system.
 * |
 */

Route::group(['middleware' => 'auth:api'], function (Router $route) {
    // Dashboard and Analytics
    $route
        ->get('/analytics/dashboard-stats', [AccountingController::class, 'getDashboardStats'])
        ->name('accounting.dashboard-stats');

    $route
        ->get('/analytics/revenue', [AccountingController::class, 'getRevenueAnalytics'])
        ->name('accounting.revenue-analytics');

    $route
        ->get('/analytics/revenue/daily', [AccountingController::class, 'getDailyRevenue'])
        ->name('accounting.daily-revenue');

    $route
        ->get('/analytics/revenue/monthly', [AccountingController::class, 'getMonthlyRevenue'])
        ->name('accounting.monthly-revenue');

    $route
        ->get('/analytics/average-booking-value', [AccountingController::class, 'getAverageBookingValue'])
        ->name('accounting.average-booking-value');

    // Booking Financial Data
    $route
        ->get('/bookings/financial', [AccountingController::class, 'getBookingFinancials'])
        ->name('accounting.booking-financials');

    $route
        ->get('/analytics/user-booking-reports', [AccountingController::class, 'getUserBookingReports'])
        ->name('accounting.user-booking-reports');

    $route
        ->get('/bookings/by-date-range', [AccountingController::class, 'getBookingsByDateRange'])
        ->name('accounting.bookings-by-date-range');

    $route
        ->get('/bookings/by-staff/{staffId}', [AccountingController::class, 'getBookingsByStaff'])
        ->name('accounting.bookings-by-staff');

    // Staff Performance
    $route
        ->get('/analytics/staff-performance', [AccountingController::class, 'getStaffPerformance'])
        ->name('accounting.staff-performance');

    $route
        ->get('/analytics/staff/{staffId}/revenue', [AccountingController::class, 'getStaffRevenue'])
        ->name('accounting.staff-revenue');

    $route
        ->get('/analytics/top-staff', [AccountingController::class, 'getTopPerformingStaff'])
        ->name('accounting.top-staff');

    // Payment and Status Analytics
    // $route
    //     ->get('/analytics/payment-methods', [AccountingController::class, 'getPaymentMethodBreakdown'])
    //     ->name('accounting.payment-methods');

    $route
        ->get('/analytics/booking-status', [AccountingController::class, 'getBookingStatusBreakdown'])
        ->name('accounting.booking-status');

    // Reports and Exports
    $route
        ->get('/reports/financial/{reportType}', [AccountingController::class, 'generateFinancialReport'])
        ->name('accounting.financial-report');

    $route
        ->get('/exports/bookings', [AccountingController::class, 'exportBookingData'])
        ->name('accounting.export-bookings');
});
