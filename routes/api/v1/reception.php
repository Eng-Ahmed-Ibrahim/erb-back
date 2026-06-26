<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\VisitorController;
use App\Http\Controllers\Api\V1\BuildingController;
use App\Http\Controllers\Api\V1\ApartmentController;
use App\Http\Controllers\Api\V1\BookingController;
use App\Http\Controllers\Api\V1\AttachmentController;
use App\Http\Controllers\Api\V1\CashierController;
use App\Http\Controllers\Api\V1\ReceptionStatsController;
use App\Http\Controllers\Api\V1\ReservationController;
use App\Http\Controllers\Api\V1\AdditionalServiceController;
use Illuminate\Routing\Router;

/*
|--------------------------------------------------------------------------
| Reception System API Routes
|--------------------------------------------------------------------------
|
| Here are the API routes for the Reception System.
| All routes are prefixed with /api/v1/reception
|
*/
Route::group(['middleware' => 'auth:api', 'prefix' => 'reception'], function (Router $route) {

    // Reception Stats Route
    Route::get('/stats', [ReceptionStatsController::class, 'index']);

    // Visitor Management Routes
    Route::prefix('visitors')->group(function ($route) {
        $route->get('/', [VisitorController::class, 'index']);
        $route->post('/', [VisitorController::class, 'store']);
        $route->get('/search', [VisitorController::class, 'searchVisitors']);
        $route->get('/search-by-id', [VisitorController::class, 'searchByIdNumber']);
        $route->get('/type/{type}', [VisitorController::class, 'getByType']);
        $route->get('/{id}', [VisitorController::class, 'show']);
        $route->put('/{id}', [VisitorController::class, 'update']);
        $route->delete('/{id}', [VisitorController::class, 'destroy']);
    });

    // Building Management Routes
    Route::prefix('buildings')->group(function ($route) {
        $route->get('/', [BuildingController::class, 'index']);
        $route->post('/', [BuildingController::class, 'store']);
        $route->get('/available-apartments', [BuildingController::class, 'availableApartments']);
        $route->get('/occupancy-stats', [BuildingController::class, 'occupancyStats']);
        $route->get('/{id}', [BuildingController::class, 'show']);
        $route->put('/{id}', [BuildingController::class, 'update']);
        $route->delete('/{id}', [BuildingController::class, 'destroy']);
        $route->get('/{id}/apartments', [BuildingController::class, 'apartments']);
    });

    // Apartment Management Routes
    Route::prefix('apartments')->group(function ($route) {
        $route->get('/', [ApartmentController::class, 'index']);
        $route->post('/', [ApartmentController::class, 'store']);
        $route->get('/available', [ApartmentController::class, 'getAvailableForDateRange']);
        $route->get('/building/{buildingId}', [ApartmentController::class, 'getAvailableByBuilding']);
        $route->get('/room-type/{roomType}', [ApartmentController::class, 'getByRoomType']);
        $route->get('/{id}', [ApartmentController::class, 'show']);
        $route->put('/{id}', [ApartmentController::class, 'update']);
        $route->delete('/{id}', [ApartmentController::class, 'destroy']);
        $route->patch('/{id}/toggle-occupancy', [ApartmentController::class, 'toggleOccupancy']);
    });


    // Booking Management Routes
    Route::prefix('bookings')->group(function ($route) {
        $route->get('/', [BookingController::class, 'index']);
        $route->post('/', [BookingController::class, 'store']);
        $route->get('/active', [BookingController::class, 'active']);
        $route->get('/visitor/{visitorId}', [BookingController::class, 'byVisitor']);
        $route->get('/apartment/{apartmentId}', [BookingController::class, 'byApartment']);
        $route->get('/date-range', [BookingController::class, 'byDateRange']);
        $route->get('/{id}', [BookingController::class, 'show']);
        $route->put('/{id}', [BookingController::class, 'update']);
        $route->delete('/{id}', [BookingController::class, 'destroy']);
        $route->patch('/{id}/checkout', [BookingController::class, 'checkout']);
    });

    // Reservation Management Routes
    Route::prefix('reservations')->group(function ($route) {
        $route->get('/', [ReservationController::class, 'index']);
        $route->post('/', [ReservationController::class, 'store']);
        $route->get('/{id}', [ReservationController::class, 'show']);
        $route->put('/{id}', [ReservationController::class, 'update']);
        $route->delete('/{id}', [ReservationController::class, 'destroy']);
        $route->patch('/{id}/confirm', [ReservationController::class, 'confirm']);
        $route->patch('/{id}/activate', [ReservationController::class, 'activate']);
        $route->patch('/{id}/cancel', [ReservationController::class, 'cancel']);
    });

    Route::get('/apartments/{id}/is-available-for-date-range', [ ApartmentController::class,  'isAvailableForDateRange'  ]);

    // Attachment Management Routes
    Route::prefix('attachments')->group(function ($route) {
        $route->get('/', [AttachmentController::class, 'index']);
        $route->post('/', [AttachmentController::class, 'store']);
        $route->get('/booking/{bookingId}', [AttachmentController::class, 'getByBooking']);
        $route->get('/type/{type}', [AttachmentController::class, 'getByType']);
        $route->get('/{id}', [AttachmentController::class, 'show']);
        $route->delete('/{id}', [AttachmentController::class, 'destroy']);
        $route->post('/booking/{bookingId}', [AttachmentController::class, 'uploadForBooking']);
    });

    // Cashier & Dashboard Routes
    Route::prefix('cashier')->group(function ($route) {
        $route->get('/dashboard', [CashierController::class, 'dashboard']);
        $route->get('/filter-bookings', [CashierController::class, 'filterBookings']);
        $route->get('/today-arrivals', [CashierController::class, 'todayArrivals']);
        $route->get('/today-checkouts', [CashierController::class, 'todayCheckouts']);
        $route->get('/reports', [CashierController::class, 'reports']);
    });

    Route::get('server-time', [ReceptionStatsController::class, 'getServerTime']);

    Route::prefix('additional-services')->group(function ($route) {
        $route->get('/', [AdditionalServiceController::class, 'index']);
        $route->post('/', [AdditionalServiceController::class, 'store']);
        $route->get('/{additionalService}', [AdditionalServiceController::class, 'show']);
        $route->put('/{additionalService}', [AdditionalServiceController::class, 'update']);
        $route->delete('/{additionalService}', [AdditionalServiceController::class, 'destroy']);
    });
});
