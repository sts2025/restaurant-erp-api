<?php
 
namespace App\Http\Controllers\Api;
 
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\SaleController;
use App\Http\Controllers\Api\AnalyticsController;
use App\Http\Controllers\Api\ShiftController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\RestaurantTableController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\ExpenseController;
use App\Http\Controllers\Api\PurchaseController;
use App\Http\Controllers\Api\BranchController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\SettingController; // ADDED
 
// =====================
// PUBLIC ROUTES
// =====================
Route::post('/login', [AuthController::class, 'login']);
 
// =====================
// PROTECTED ROUTES
// =====================
Route::middleware('auth:sanctum')->group(function () {
 
    // ── AUTH ───────────────────────────────────────
    Route::get('/me',      [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
 
    // ── DASHBOARD ──────────────────────────────────
    Route::get('/dashboard/stats',     [DashboardController::class, 'stats']);
    Route::get('/analytics/dashboard', [AnalyticsController::class, 'dashboard']);
 
    // ── SALES ──────────────────────────────────────
    Route::get('/sales',                    [SaleController::class, 'index']);
    Route::post('/sales',                   [SaleController::class, 'store']);
    Route::get('/sales/{sale}',             [SaleController::class, 'show']);
    Route::get('/sales/{id}/reprint',       [SaleController::class, 'reprint']);
    Route::post('/sales/{sale}/void',       [SaleController::class, 'voidSale']);
    Route::post('/sales/hold',              [SaleController::class, 'hold']);
    Route::get('/held-orders',              [SaleController::class, 'heldOrders']);
    Route::post('/held-orders/{id}/resume', [SaleController::class, 'resume']);
 
    // ── REPORTS ────────────────────────────────────
    Route::get('/reports/daily',    [SaleController::class,   'dailyReport']);
    Route::get('/reports/cashiers', [ReportController::class, 'cashierPerformance']);
    Route::get('/reports/products', [ReportController::class, 'productSales']);
    Route::get('/reports/z-report', [ReportController::class, 'zReport']);
 
    // ── PRODUCTS ───────────────────────────────────
    Route::get('/products',               [ProductController::class, 'index']);
    Route::post('/products',              [ProductController::class, 'store']);
    Route::get('/products/{product}',     [ProductController::class, 'show']);
    Route::put('/products/{product}',     [ProductController::class, 'update']);
    Route::delete('/products/{product}',  [ProductController::class, 'destroy']);
    Route::post('/products/update-stock', [ProductController::class, 'updateStock']);
    Route::get('/stock-movements',        [ProductController::class, 'stockMovements']);
 
    // ── CATEGORIES ─────────────────────────────────
    Route::get('/categories',              [CategoryController::class, 'index']);
    Route::post('/categories',             [CategoryController::class, 'store']);
    Route::put('/categories/{category}',   [CategoryController::class, 'update']);
    Route::delete('/categories/{category}',[CategoryController::class, 'destroy']);
 
    // ── TABLES ─────────────────────────────────────
    Route::get('/tables',                      [RestaurantTableController::class, 'index']);
    Route::post('/tables',                     [RestaurantTableController::class, 'store']);
    Route::put('/tables/{restaurantTable}',    [RestaurantTableController::class, 'update']);
    Route::delete('/tables/{restaurantTable}', [RestaurantTableController::class, 'destroy']);
    Route::post('/tables/{table}/close',       [RestaurantTableController::class, 'close']);
    Route::post('/tables/transfer',            [RestaurantTableController::class, 'transfer']);
    Route::post('/tables/merge',               [RestaurantTableController::class, 'merge']);
    Route::post('/tables/split',               [RestaurantTableController::class, 'split']);
 
    // ── USERS ──────────────────────────────────────
    Route::get('/users',            [UserController::class, 'index']);
    Route::post('/users',           [UserController::class, 'store']);
    Route::put('/users/{user}',     [UserController::class, 'update']);
    Route::delete('/users/{user}',  [UserController::class, 'destroy']);
 
    // ── EXPENSES ───────────────────────────────────
    Route::get('/expenses',              [ExpenseController::class, 'index']);
    Route::post('/expenses',             [ExpenseController::class, 'store']);
    Route::put('/expenses/{expense}',    [ExpenseController::class, 'update']);   // ADDED
    Route::delete('/expenses/{expense}', [ExpenseController::class, 'destroy']);
 
    // ── PURCHASES ──────────────────────────────────
    Route::get('/purchases',  [PurchaseController::class, 'index']);
    Route::post('/purchases', [PurchaseController::class, 'store']);
 
    // ── BRANCHES ───────────────────────────────────
    Route::get('/branches',            [BranchController::class, 'index']);
    Route::post('/branches',           [BranchController::class, 'store']);
    Route::put('/branches/{branch}',   [BranchController::class, 'update']);   // ADDED
    Route::get('/branches/active',     [BranchController::class, 'activeBranch']); // ADDED
    Route::post('/switch-branch',      [BranchController::class, 'switch']);
    Route::delete('/branches/{branch}', [BranchController::class, 'destroy']);
 
    // ── SETTINGS ───────────────────────────────────
    Route::get('/settings', [SettingController::class, 'index']);   // ADDED
    Route::put('/settings', [SettingController::class, 'update']);  // ADDED
 
    // ── SHIFTS ─────────────────────────────────────
    Route::post('/shifts/open',          [ShiftController::class, 'open']);
    Route::post('/shifts/close',         [ShiftController::class, 'close']);
    Route::get('/shifts/active',         [ShiftController::class, 'active']);
    Route::get('/shifts/{id}/report',    [ShiftController::class, 'getShiftReport']);
    Route::get('/shifts/{shift}/report', [ShiftController::class, 'report']);
});
 








































