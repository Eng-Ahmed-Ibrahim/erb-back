<?php

use App\Http\Controllers\Api\V1\Menu\ProductController;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;

Route::group(['middleware' => 'auth:api', 'prefix' => 'product'], function (Router $route) {
    $route->get('/show/{id}', [ProductController::class, 'show']);
    $route->get('/subcategory/{id}', [ProductController::class, 'filterBySubCategory']);
    $route->get('/', [ProductController::class, 'index']);
    $route->get('/subcategories/department', [ProductController::class, 'subcategory']);
    $route->get('/changeuuid', [ProductController::class, 'changeuuid']);
});
