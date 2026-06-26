<?php

use App\Http\Controllers\Api\V1\ShiftController;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'shifts'], function (Router $route) {

    $route->get('/', [ShiftController::class, 'index']);
    // $route->get('/{id}', [ShiftController::class, 'show']);
    $route->post('/create', [ShiftController::class, 'store']);
    // $route->put('/update/{id}', [ShiftController::class, 'update']);
    $route->delete('/delete/{id}', [ShiftController::class, 'delete']);
    $route->get('/cashiers', [ShiftController::class, 'getAllCashiers']);
});
