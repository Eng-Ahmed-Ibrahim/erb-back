<?php

use App\Http\Controllers\Api\V1\OrderController;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;

Route::get('/detailed_reports', [OrderController::class, 'ordersReport']);
Route::get('/payment_report', [OrderController::class, 'paymentReport']);
Route::get('/department_products_report', [OrderController::class, 'getDeoartmentProductsReport']);

Route::group(['middleware' => 'auth:api', 'prefix' => 'orders'], function (Router $route) {
    $route->get('/payment/method/{client_type_id}', [OrderController::class, 'paymentMethod']);  // done
    $route->get('/clients/type', [OrderController::class, 'clientsType']);  // done
    $route->get('/clients/{client_type_id}', [OrderController::class, 'clients']);  // done
    $route->get('/discount/reasons', [OrderController::class, 'discountReasons']);  // done
    $route->post('/monthly-discount-status', [OrderController::class, 'monthlyDiscountStatus']);

    $route->get('/deleted', [OrderController::class, 'getDeletedOrders']);  // done
    $route->post('/deleted/update/status/{id}', [OrderController::class, 'updateDeletedOrderStatus']);

    $route->post('/review-price', [OrderController::class, 'reviewOrderPrice']);

    $route->post('/create', [OrderController::class, 'store']);  // done

    $route->put('/update/{id}', [OrderController::class, 'update']);  // done
    $route->post('/update/status/{id}', [OrderController::class, 'updateStatus']);  // done
    $route->post('/update/comment/{id}', [OrderController::class, 'updateComment']);  // done
    $route->post('/update/payment-method/{id}', [OrderController::class, 'updatePaymentMethod']);  // done
    // 
    $route->delete('/delete/{id}', [OrderController::class, 'delete']);  // done

    $route->post('/product/add/{id}', [OrderController::class, 'addProduct']);  // done
    $route->get('/print-order/{order_id}', [OrderController::class, 'isPrinted']);  // done
    $route->post('/product/update/{id}', [OrderController::class, 'updateProduct']);  // done
    $route->delete('/product/delete/{id}', [OrderController::class, 'deleteProduct']);  // done

    $route->post('/add-payable/{id}', [OrderController::class, 'storePayable']);  // done

    $route->get('external-orders-report', [OrderController::class, 'externalOrdersReport']);

    $route->get('/', [OrderController::class, 'index']);  // done
    $route->get('/{id}', [OrderController::class, 'show']);  // done
    $route->get('/status/{status}', [OrderController::class, 'status']);  // done
    $route->get('/show/tables', [OrderController::class, 'showTables']);  // done
    $route->get('/show/tables/{department_id}', [OrderController::class, 'showTables2']);  // done
    $route->get('/department/{department_id}', [OrderController::class, 'department']);  // done
    $route->get('/client/{client_id}', [OrderController::class, 'client']);  // done
    $route->get('/client/type/{client_type_id}', [OrderController::class, 'clientType']);  // done
    $route->get('/', [OrderController::class, 'index']);  // done
    $route->get('/{id}', [OrderController::class, 'show']);  // done
    $route->get('/status/{status}', [OrderController::class, 'status']);  // done
    $route->get('/show/tables', [OrderController::class, 'showTables']);  // done
    $route->get('/department/{department_id}', [OrderController::class, 'department']);  // done
    $route->get('/client/{client_id}', [OrderController::class, 'client']);  // done
    $route->get('/client/type/{client_type_id}', [OrderController::class, 'clientType']);  // done

    $route->get('/paymrnt/method/{type}', [OrderController::class, 'postpaid']);  // done
    $route->get('/payment_methods', [OrderController::class, 'payment_method']);  // done

    $route->get('/sales', [OrderController::class, 'sales']);  // report

    $route->post('/execute/{id}', [OrderController::class, 'execute']);
    $route->get('/check_table_num/{table_num}', [OrderController::class, 'checkTableNum']);

    $route->get('/report/product', [OrderController::class, 'reportOfProduct']);  // report
});
