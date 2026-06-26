<?php

use App\Models\SubCategory;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::group(['namespace' => 'api\V1'], function () {
    require __DIR__.'/api/v1/product.php';
    require __DIR__.'/api/v1/order.php';
    require __DIR__.'/api/v1/users.php';
    require __DIR__.'/api/v1/store.php';
    require __DIR__.'/api/v1/shift.php';
    require __DIR__.'/api/v1/employees.php';
    require __DIR__.'/api/v1/reception.php';
    require __DIR__.'/api/v1/accounting.php';
    require __DIR__.'/api/v1/audit.php';
    require __DIR__.'/api/v1/audit.php';
    require __DIR__.'/api/v1/activities_subscriptions.php';
    require __DIR__ . '/api/v1/inventory_ledger.php';
    require __DIR__ . '/api/v1/inventory_blind_count.php';
    require __DIR__ . '/api/v1/settings.php';
    require __DIR__ . '/api/v1/membership_cards.php';
});

Route::get('/get-file/{sub_category_id}', function ($sub_category_id) {
    $sub_category = SubCategory::find($sub_category_id);
    $file = public_path($sub_category->image);

    return response()->file($file);
});
