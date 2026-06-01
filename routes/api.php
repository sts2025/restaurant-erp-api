<?php
namespace App\Http\Controllers\Api;

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\SaleController;
use App\Http\Controllers\Api\AnalyticsController;
use App\Http\Controllers\Api\ShiftController;
//use App\Http\Controllers\Api\AttendanceController;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\RestaurantTableController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\ExpenseController;
use App\Http\Controllers\Api\PurchaseController;
use App\Http\Controllers\Api\BranchController;

// =====================
// PUBLIC ROUTES
// =====================
Route::post('/login', [AuthController::class, 'login']);

// =====================
// PROTECTED ROUTES
// =====================
Route::middleware('auth:sanctum')->group(function () {

    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);



    // Route::post('/clock-in', [AttendanceController::class, 'clockIn']);

    Route::get('/analytics/dashboard', [AnalyticsController::class, 'dashboard']);
    Route::get( '/sales', [SaleController::class, 'index']);
    Route::post('/sales/hold',[SaleController::class, 'hold']);
    Route::get('/held-orders',[SaleController::class, 'heldOrders']);
    Route::post('/held-orders/{id}/resume',[SaleController::class,'resume']);

    Route::post('/sales/{sale}/void',[SaleController::class, 'voidSale']);

    Route::post('/shifts/open', [ShiftController::class, 'open']);
    Route::post('/shifts/close', [ShiftController::class, 'close']);
    Route::get('/shifts/active', [ShiftController::class, 'active']);
    Route::get('/shifts/{id}/report', [ShiftController::class, 'getShiftReport']);
    Route::get('/shifts/{shift}/report',[ShiftController::class, 'report']);

    Route::get('/categories', [CategoryController::class, 'index']);
    Route::post('/categories', [CategoryController::class, 'store']);
    Route::put('/categories/{category}', [CategoryController::class, 'update']);
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy']);

    Route::post('/sales', [SaleController::class, 'store']);
    Route::get('/sales', [SaleController::class, 'index']);
    Route::get('/reports/daily',[SaleController::class, 'dailyReport']);
    Route::get('/sales/{sale}', [SaleController::class, 'show']);
    Route::get( '/sales/{id}/reprint', [SaleController::class, 'reprint'] );

    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);
   
    Route::get('/products', [ProductController::class, 'index']);
    Route::post('/products', [ProductController::class, 'store']);
    Route::put('/products/{product}', [ProductController::class, 'update']);
    Route::delete('/products/{product}', [ProductController::class, 'destroy']);
    Route::post('/products/update-stock', [ProductController::class, 'updateStock']);

    Route::get('/tables', [RestaurantTableController::class, 'index']);
    Route::post('/tables', [RestaurantTableController::class, 'store']);
    Route::put('/tables/{restaurantTable}', [RestaurantTableController::class, 'update']);
    Route::delete('/tables/{restaurantTable}', [RestaurantTableController::class, 'destroy']);
    Route::post('/tables/{table}/close',[RestaurantTableController::class,'close']);
    Route::post('/tables/transfer',[RestaurantTableController::class,'transfer']);

    Route::get('/users', [UserController::class, 'index']);
    Route::post('/users', [UserController::class,'store']);
    Route::put('/users/{user}', [UserController::class, 'update']);
    Route::delete('/users/{user}', [UserController::class,'destroy']);

    Route::get('/expenses',[ExpenseController::class, 'index']);
    Route::post('/expenses',[ExpenseController::class, 'store']);
    Route::delete('/expenses/{expense}',[ExpenseController::class, 'destroy']);

    Route::get('/purchases',[PurchaseController::class, 'index']);
    Route::post('/purchases',[PurchaseController::class, 'store']);
    Route::get('/reports/cashiers',[ReportController::class, 'cashierPerformance']);

    Route::get('/reports/products',[ReportController::class, 'productSales']);
    Route::get('/reports/z-report',[ReportController::class, 'zReport']);

    Route::post( '/switch-branch', [BranchController::class, 'switch'] );
    Route::get('/branches',[BranchController::class, 'index']);
    Route::post( '/branches', [BranchController::class, 'store']);

});
