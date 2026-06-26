<?php

use App\Http\Controllers\Api\V1\EmployeeController;
use App\Http\Controllers\Api\V1\EmployeeDepartmentsController;
use App\Http\Controllers\Api\V1\IncentiveController;
use App\Http\Controllers\Api\V1\JobController;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;

Route::group(['middleware' => 'auth:api'], function (Router $route) {
    Route::prefix('employees')->group(function () use ($route) {
        $route->get('/', [EmployeeController::class, 'index']);
        $route->post('/', [EmployeeController::class, 'store']);
        $route->get('{uuid}', [EmployeeController::class, 'show']);
        $route->put('{uuid}', [EmployeeController::class, 'update']);
        $route->delete('{uuid}', [EmployeeController::class, 'destroy']);
        $route->get('/types', [EmployeeController::class, 'getEmployyeTypes']);
    });
    Route::get('/departments', [EmployeeController::class, 'departments']);
    Route::get('/types', [EmployeeController::class, 'getEmployyeTypes']);
    Route::get('/incentives-types', [IncentiveController::class, 'types']);

    Route::prefix('jobs')->group(function () use ($route) {
        $route->get('/', [JobController::class, 'index']);
        $route->post('/', [JobController::class, 'store']);
        $route->get('{uuid}', [JobController::class, 'show']);
        $route->put('{uuid}', [JobController::class, 'update']);
        $route->delete('{uuid}', [JobController::class, 'destroy']);
    });

    Route::prefix('incentives')->group(function () use ($route) {
        $route->get('/', [IncentiveController::class, 'index']);
        $route->post('/', [IncentiveController::class, 'store']);
        $route->post('lock-incentives', [IncentiveController::class, 'LockCurrentMonthIncentives']);
        $route->get('{uuid}', [IncentiveController::class, 'show']);
        $route->put('{uuid}', [IncentiveController::class, 'update']);
        $route->put('/', [IncentiveController::class, 'updateAll']);
        $route->get('/types', [IncentiveController::class, 'types']);
        $route->delete('{uuid}', [IncentiveController::class, 'destroy']);
    });

    Route::prefix('employee-departments')->group(function () use ($route) {
        $route->get('/', [EmployeeDepartmentsController::class, 'index']);
        $route->post('/', [EmployeeDepartmentsController::class, 'store']);
        $route->put('{uuid}', [EmployeeDepartmentsController::class, 'update']);
        $route->delete('{uuid}', [EmployeeDepartmentsController::class, 'destroy']);
    });
});
