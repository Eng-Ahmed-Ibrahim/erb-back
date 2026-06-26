<?php

use App\Http\Controllers\Api\V1\User\UserController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\ClientController;
use App\Http\Controllers\Api\V1\ClientTypeController;
use App\Http\Controllers\Api\V1\DepartmentController;
use App\Http\Controllers\Api\V1\DepartmentInventoryReviewController;
use App\Http\Controllers\Api\V1\DiscountReasonController;
use App\Http\Controllers\Api\V1\InventoryArchiveController;
use App\Http\Controllers\Api\V1\CategoryInventoryReportController;
use App\Http\Controllers\Api\V1\InvoiceController;
use App\Http\Controllers\Api\V1\PayableController;
use App\Http\Controllers\Api\V1\OrderPayableController;
use App\Http\Controllers\Api\V1\PaymentMethodController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\RecipeCategoryController;
use App\Http\Controllers\Api\V1\RecipeCategoryParentController;
use App\Http\Controllers\Api\V1\RecipeController;
use App\Http\Controllers\Api\V1\RequestController;
use App\Http\Controllers\Api\V1\RoleController;
use App\Http\Controllers\Api\V1\SubCategoryController;
use App\Http\Controllers\Api\V1\SupplierController;
use App\Http\Controllers\Api\V1\TaintedInvoiceController;
use App\Http\Controllers\Api\V1\TransactionController;
use App\Http\Controllers\Api\V1\UnitController;
use App\Http\Controllers\Api\V1\WaiterController;
use App\Http\Controllers\Api\V1\WarehouseSectionsController;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;

Route::get('department/department-balance', [RecipeController::class, 'getDepartmentBalanceFromToDate']);
// Route::get('/inventory', [InventoryArchiveController::class , 'index2']);

Route::get('department/sections', [DepartmentController::class, 'getDepartmentSections']);

Route::prefix('store')->middleware('auth:api')->group(function (Router $route) {
    $route->prefix('categories')->group(function () use ($route) {
        $route->get('/', [CategoryController::class, 'index']);  // finished
        $route->get('/all', [CategoryController::class, 'all']);  // finished
        $route->get('/{id}', [CategoryController::class, 'show']);  // finished
        $route->post('/create', [CategoryController::class, 'store']);  // finished
        $route->put('/update/{id}', [CategoryController::class, 'update']);  // finished
        $route->delete('/delete/{id}', [CategoryController::class, 'delete']);  // finished
    });
    Route::get('/searchItems', [RecipeCategoryParentController::class, 'searchItems']);
    Route::get('/department-recipe-search', [DepartmentController::class, 'getDepartmentReciepes']);

    $route->prefix('sub_categories')->group(function () use ($route) {
        $route->get('/', [SubCategoryController::class, 'index']);  // finished
        $route->get('/mobile', [SubCategoryController::class, 'index2']);  // finished
        $route->get('/test/{id}', [SubCategoryController::class, 'show']);
        $route->get('/all', [SubCategoryController::class, 'all']);  // finished
        $route->post('/create', [SubCategoryController::class, 'store']);  // finished
        $route->put('/update/{id}', [SubCategoryController::class, 'update']);  // finished
        $route->delete('/delete/{id}', [SubCategoryController::class, 'delete']);  // finished
        $route->get('/filter_by_category/{category_id}', [SubCategoryController::class, 'filterByCategory']);  // finished
    });

    $route->prefix('department')->group(function () use ($route) {
        $route->get('/', [DepartmentController::class, 'index']);  // finished
        $route->get('/all', [DepartmentController::class, 'getAllDepartment']);  // finished
        $route->get('/get_all_user_by_department_id/{id}', [DepartmentController::class, 'getAllUserByDepartmentId']);  // finished
        $route->get('/{id}', [DepartmentController::class, 'show']);  // finished
        $route->post('/create', [DepartmentController::class, 'store']);  // finished
        $route->put('/update/{id}', [DepartmentController::class, 'update']);  // finished
        $route->delete('/delete/{id}', [DepartmentController::class, 'delete']);  // finished
        Route::get('/department-balance', [RecipeController::class, 'getDepartmentBalanceFromToDate']);
        $route->get('/orders/{department_id}', [DepartmentController::class, 'orders']);
        // $route->get('/search',[DepartmentController::class, 'search']);
        $route->post('/update_recipe_price', [DepartmentController::class, 'updateRecipesPrice']);
        $route->post('/eliminate-over-quantity', [DepartmentController::class, 'eliminateOverQuantity']);
        $route->post('/update-actual-quantities', [DepartmentController::class, 'updateDepartmentActualQuantities']);
    });

    $route->prefix('waiter')->group(function () use ($route) {
        $route->get('/', [WaiterController::class, 'index']);  // finished
        $route->get('/all', [WaiterController::class, 'getAllWaiter']);  // finished
        $route->get('/{id}', [WaiterController::class, 'show']);  // finished
        $route->post('/create', [WaiterController::class, 'store']);  // finished
        $route->put('/update/{id}', [WaiterController::class, 'update']);  // finished
        $route->delete('/delete/{id}', [WaiterController::class, 'delete']);  // finished
        $route->get('/orders/{waiter_id}', [WaiterController::class, 'waiterOrders']);  // finished
    });

    $route->get('/cost-report', [ProductController::class, 'getProductsCostReport']);

    $route->prefix('products')->group(function () use ($route) {
        $route->get('/', [ProductController::class, 'index']);
        $route->get('/new', [ProductController::class, 'new']);
        $route->get('/{id}', [ProductController::class, 'show']);
        $route->post('/create', [ProductController::class, 'store']);
        $route->put('/update/{id}', [ProductController::class, 'update']);
        $route->delete('/delete/{id}', [ProductController::class, 'delete']);
        $route->post('/add/price/{product_id}', [ProductController::class, 'addPrice']);
        $route->put('/update/price/{id}', [ProductController::class, 'updatePrice']);
        $route->delete('/delete/price/{id}', [ProductController::class, 'deletePrice']);
        $route->post('/review/{id}', [ProductController::class, 'review']);
        $route->get('/category/{id}', [ProductController::class, 'filterByCategory']);
        $route->get('/subcategory/{id}', [ProductController::class, 'filterBySubCategory']);
        // $route->get('/cost-report', [ProductController::class, 'getProductsCostReport']);

        $route->post('/department/add', [ProductController::class, 'addToDepartment']);
        $route->put('/department/update/{id}', [ProductController::class, 'editProductDepartment']);

        $route->delete('/department/delete/{id}', [ProductController::class, 'removeFromDepartment']);
        $route->post('/recipts/add', [ProductController::class, 'addRecipe']);

        $route->delete('/{product}/recipe/delete/{id}', [ProductController::class, 'removeRecipe']);
        $route->get('/subcategories/department', [ProductController::class, 'subcategory']);  // here
        $route->get('/department/{id}', [ProductController::class, 'department']);  // here
    });

    // $route->get('/departments_with_discount',  [ClientTypeController::class, 'getDepartmentsWithDiscount']);

    $route->prefix('departments_with_discount')->group(function () use ($route) {
        $route->get('/', [ClientTypeController::class, 'getDepartmentsWithDiscount']);
        $route->post('/update', [ClientTypeController::class, 'updateDepartmentsWithDiscount']);
    });
    Route::get('/inventory_balance', [InventoryArchiveController::class, 'index']);

    $route->prefix('client_type')->group(function () use ($route) {
        $route->get('/', [ClientTypeController::class, 'index']);
        $route->get('/{id}', [ClientTypeController::class, 'show']);

        $route->get('payment_method/{id}', [ClientTypeController::class, 'payment_method']);
        $route->post('/create', [ClientTypeController::class, 'store']);
        $route->put('/update/{id}', [ClientTypeController::class, 'update']);
        $route->delete('/delete/{id}', [ClientTypeController::class, 'destroy']);
    });
    $route->prefix('client')->group(function () use ($route) {
        $route->get('/', [ClientController::class, 'index']);
        $route->get('/{id}', [ClientController::class, 'show']);
        $route->post('/create', [ClientController::class, 'store']);
        $route->put('/update/{id}', [ClientController::class, 'update']);
        $route->delete('/delete/{id}', [ClientController::class, 'destroy']);
    });
    $route->prefix('discount_reason')->group(function () use ($route) {
        $route->get('/', [DiscountReasonController::class, 'index']);
        $route->get('/{id}', [DiscountReasonController::class, 'show']);
        $route->post('/create', [DiscountReasonController::class, 'store']);
        $route->put('/update/{id}', [DiscountReasonController::class, 'update']);
        $route->delete('/delete/{id}', [DiscountReasonController::class, 'destroy']);
    });
    $route->prefix('payment_method')->group(function () use ($route) {
        $route->get('/', [PaymentMethodController::class, 'index']);
        $route->get('/{id}', [PaymentMethodController::class, 'show']);
        $route->post('/create', [PaymentMethodController::class, 'store']);
        $route->put('/update/{id}', [PaymentMethodController::class, 'update']);
        $route->delete('/delete/{id}', [PaymentMethodController::class, 'destroy']);
    });

    $route->prefix('recipe_category')->group(function () use ($route) {
        $route->get('/', [RecipeCategoryController::class, 'index']);
        $route->get('/all', [RecipeCategoryController::class, 'all']);  // finished
        $route->get('/{id}', [RecipeCategoryController::class, 'show']);  // finished
        $route->post('/create', [RecipeCategoryController::class, 'store']);  // finished
        $route->put('/update/{id}', [RecipeCategoryController::class, 'update']);  // finished
        $route->delete('/delete/{id}', [RecipeCategoryController::class, 'delete']);  // finished
        $route->get('/allById/{category_id}', [RecipeCategoryController::class, 'getByCategory']);
    });

    Route::get('/warehouse_sections', [WarehouseSectionsController::class, 'index']);

    $route->prefix('recipe_category_parent')->group(function () use ($route) {
        $route->get('/', [RecipeCategoryParentController::class, 'index']);
        $route->get('/all', [RecipeCategoryParentController::class, 'all']);
        $route->get('/{id}', [RecipeCategoryParentController::class, 'show']);
        $route->post('/create', [RecipeCategoryParentController::class, 'store']);
        $route->put('/update/{id}', [RecipeCategoryParentController::class, 'update']);
        $route->delete('/delete/{id}', [RecipeCategoryParentController::class, 'delete']);
        // $route->get('/searchItems', [RecipeCategoryParentController::class, 'searchItems']);
    });

    Route::prefix('recipe')->group(function () use ($route) {
        // $route->get('/department/{id}', [RecipeController::class, 'departmentShow']);
        $route->get('/{id}', [RecipeController::class, 'show']);
        $route->get('/', [RecipeController::class, 'index']);
        $route->get('/all/paginated', [RecipeController::class, 'allPaginated']);
        $route->post('/create', [RecipeController::class, 'store']);
        $route->put('/update/{id}', [RecipeController::class, 'update']);
        $route->post('/change-status/{id}', [RecipeController::class, 'changeRecipeStatus']);
        $route->delete('/delete/{id}', [RecipeController::class, 'delete']);
        $route->get('/filter_by_category/{id}', [RecipeController::class, 'filterByCategory']);
        $route->get('/get_repices/under_limt', [RecipeController::class, 'getRepicesUnderLimt']);
        $route->get('/expire-limit/expire_date_before_days', [RecipeController::class, 'getRecipesHasExpireDateBeforeDays']);
        $route->get('/expire-limit/expire_date_before_days/{recipe_id}', [RecipeController::class, 'showOneExpireDate']);
        $route->get('/store/totals', [RecipeController::class, 'totalStores']);
        $route->post('/department/add', [RecipeController::class, 'addToDepartment']);
        $route->delete('/department/delete/{id}', [RecipeController::class, 'removeFromDepartment']);
        $route->get('/recipe-invoices-report/statistics/{recipe_id}', [RecipeController::class, 'recipeInvoiceReport']);
        $route->get('/allById/{recipe_category_id}', [RecipeController::class, 'getRecipesByCategory']);
        $route->get('/products/{recipe_id}', [RecipeController::class, 'recipeProducts']);
        // $route->post('/update_recipe_department', [RecipeController::class, 'updateRecipeDepartment']);
        $route->delete('/admin-delete/{recipe_id}', [RecipeController::class, 'deleteRecipe']);
    });

    $route->prefix('recipes')->group(function () use ($route) {
        $route->get('/invoices', [RecipeController::class, 'getAllRecipeInvoices']);
    });
    $route->prefix('inventory')->group(function () use ($route) {
        $route->get('/get-discrepance-reviews', [DepartmentInventoryReviewController::class, 'index']);
    });
    // inventory/
    $route->prefix('supplier')->group(function () use ($route) {
        $route->get('/', [SupplierController::class, 'index']);
        $route->get('/{id}', [SupplierController::class, 'show']);
        $route->post('/create', [SupplierController::class, 'store']);
        $route->put('/update/{id}', [SupplierController::class, 'update']);
        $route->delete('/delete/{id}', [SupplierController::class, 'delete']);
        $route->get('/search/{search}', [SupplierController::class, 'search']);
        $route->get('{id}/invoices', [SupplierController::class, 'showInvoices']);
    });

    $route->prefix('unit')->group(function () use ($route) {
        $route->get('/', [UnitController::class, 'index']);
        $route->get('/{id}', [UnitController::class, 'show']);
        $route->post('/create', [UnitController::class, 'store']);
        $route->post('/update/{id}', [UnitController::class, 'update']);
        $route->delete('/delete/{id}', [UnitController::class, 'delete']);
    });

    $route->prefix('user')->group(function () use ($route) {
        $route->get('/', [UserController::class, 'index']);  // finished
        $route->post('/create', [UserController::class, 'store']);  // finished
        $route->get('/stocks', [UserController::class, 'getStockUsers']);  // Get users with stocks role
        $route->get('/{id}', [UserController::class, 'show']);
        $route->put('/{id}/update_role', [UserController::class, 'updateRole']);
        $route->post('/update/{user_id}', [UserController::class, 'update']);
        $route->post('update-department/{user_id}', [UserController::class, 'updateDepartment']);

        $route->delete('/delete/{id}', [UserController::class, 'delete']);
        $route->get('/all/users', [UserController::class, 'all']);
        $route->get('/orders/{user_id}', [UserController::class, 'orders']);
        // admin-login
        $route->get('admin-login/{id}', [UserController::class, 'adminLogin']);
    });

    $route->prefix('invoice')->group(function () use ($route) {
        $route->get('/', [InvoiceController::class, 'index']);
        $route->get('/{id}', [InvoiceController::class, 'show']);
        $route->post('/create', [InvoiceController::class, 'store']);
        $route->put('/update/{id}', [InvoiceController::class, 'update']);
        $route->put('/update_quantity/{invoice_id}', [InvoiceController::class, 'updateQuantity']);
        $route->put('/update_data/{invoice_id}', [InvoiceController::class, 'updateInvoiceData']);
        $route->delete('/delete/{invoice_id}', [InvoiceController::class, 'delete']);
        $route->get('/filter_by_from_department/{department_id}', [InvoiceController::class, 'filterByFromDepartment']);
        $route->get('/filter_by_to_department/{department_id}', [InvoiceController::class, 'filterByToDepartment']);
        $route->get('/chenge_status/{id}/{status}', [InvoiceController::class, 'changeStatus']);
        $route->get('/get_invoices_based_on_type/{type}', [InvoiceController::class, 'getInvoicesBasedOnType']);
        $route->get('/filter/get_recipes/out_going_from_to_date/{department_id}', [InvoiceController::class, 'getRecipesOutGoingFromToDate']);
        $route->get('/filter/get_recipes/out_going_to_department_from_to_date/{department_id}', [InvoiceController::class, 'getRecipesOutGoingToDepartmentFromToDate']);
        $route->get('/filter/get_recipes/out_going_from_department_from_to_date/{department_id}', [InvoiceController::class, 'getRecipesOutGoingFromDepartmentFromToDate']);
        $route->get('/suppliers/invoices', [InvoiceController::class, 'getInCommingInvoices']);
        $route->get('/filter/get_recipes/supplier_from_to_date/{supplier_id}', [InvoiceController::class, 'getSuppliersRecipesFromToDate']);
        $route->post('/move-invoice-to-department', [InvoiceController::class, 'moveInvoiceToDepartment']);

        // New routes for recipe management
        $route->post('/{id}/add-recipe', [InvoiceController::class, 'addRecipe']);
        $route->delete('/{id}/remove-recipe/{recipe_id}', [InvoiceController::class, 'removeRecipe']);
        $route->delete('/{id}/remove-recipe/{recipe_id}/{source_invoice_id}', [InvoiceController::class, 'removeRecipe']);
    });

    $route->prefix('/tainted-invoices')->group(function () use ($route) {
        $route->get('/', [TaintedInvoiceController::class, 'index']);
        $route->get('/{id}', [TaintedInvoiceController::class, 'show']);
        $route->post('/create', [TaintedInvoiceController::class, 'store']);
        $route->put('/update/{id}', [TaintedInvoiceController::class, 'update']);
    });

    $route->prefix('request')->group(function () use ($route) {
        $route->get('/', [RequestController::class, 'index']);
        $route->get('/{id}', [RequestController::class, 'show']);
        $route->post('/create', [RequestController::class, 'store']);
        $route->put('/update/{id}', [RequestController::class, 'update']);
        $route->delete('/delete/{id}', [RequestController::class, 'delete']);
        $route->get('/filter_by_user/{user_id}', [RequestController::class, 'filterByUsaer']);
        $route->get('/filter_by_department/{department_id}', [RequestController::class, 'filterByDepartment']);
        $route->get('/chenge_status/{id}/{status}', [RequestController::class, 'changeStatus']);
    });
    Route::get('/inventory_archive/report', [InventoryArchiveController::class, 'getInventoryArchiveReport']);
    Route::get('/inventory_archive/capture-dates', [InventoryArchiveController::class, 'getAvailableCaptureDates']);
    Route::get('/category_inventory_report', [CategoryInventoryReportController::class, 'getCategoryReport']);

    $route->prefix('payable')->group(function () use ($route) {
        $route->get('/', [PayableController::class, 'index']);
        $route->get('/{id}', [PayableController::class, 'show']);
        $route->post('/create', [PayableController::class, 'store']);
        $route->put('/update/{id}', [PayableController::class, 'update']);
        $route->delete('/delete/{id}', [PayableController::class, 'delete']);
    });

    $route->prefix('order-payable')->group(function () use ($route) {
        $route->get('/', [OrderPayableController::class, 'index']);
        $route->get('/orders/options', [OrderPayableController::class, 'orderOptions']);
        $route->get('/{id}', [OrderPayableController::class, 'show']);
        $route->post('/create', [OrderPayableController::class, 'store']);
        $route->put('/update/{id}', [OrderPayableController::class, 'update']);
        $route->delete('/delete/{id}', [OrderPayableController::class, 'delete']);
    });

    $route->prefix('transaction')->group(function () use ($route) {
        $route->get('/', [TransactionController::class, 'index']);
        $route->get('/{id}', [TransactionController::class, 'show']);
        $route->post('/create', [TransactionController::class, 'store']);
        $route->put('/update/{id}', [TransactionController::class, 'update']);
        $route->delete('/delete/{id}', [TransactionController::class, 'delete']);
    });

    $route->prefix('permission')->group(function () use ($route) {
        $route->get('/', [RoleController::class, 'permissions']);
    });

    $route->prefix('role')->group(function () use ($route) {
        $route->get('/', [RoleController::class, 'index']);
        $route->get('/{id}', [RoleController::class, 'show']);
        $route->post('/create', [RoleController::class, 'store']);
        $route->put('/update/{id}', [RoleController::class, 'update']);
    });
});
