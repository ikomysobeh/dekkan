<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserApiController;
use App\Http\Controllers\Api\DashboardApiController;
use App\Http\Controllers\Api\SaleApiController;
use App\Http\Controllers\Api\ProductApiController;
use App\Http\Controllers\Api\PurchaseApiController;
use App\Http\Controllers\Api\PaymentReceiptApiController;

// Public
Route::post('/login', [AuthController::class, 'login']);

// Protected — require a valid Sanctum token
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/dashboard', [DashboardApiController::class, 'index']);

    // ---- Products (specific paths BEFORE /{id}) ----
    Route::get('/products/search', [ProductApiController::class, 'search']);
    Route::get('/products/alerts', [ProductApiController::class, 'alerts']);
    Route::get('/products/by-barcode/{barcode}', [ProductApiController::class, 'byBarcode']);
    Route::get('/products', [ProductApiController::class, 'index']);
    Route::get('/products/{id}', [ProductApiController::class, 'show']);
    Route::post('/products', [ProductApiController::class, 'store']);
    Route::post('/products/{id}', [ProductApiController::class, 'update']); // multipart update
    Route::delete('/products/{id}', [ProductApiController::class, 'destroy']);

    // ---- Sales ----
    Route::get('/sales', [SaleApiController::class, 'index']);
    Route::get('/sales/{id}', [SaleApiController::class, 'show']);
    Route::post('/sales', [SaleApiController::class, 'store']);
    Route::put('/sales/{id}', [SaleApiController::class, 'update']);
    Route::delete('/sales/{id}', [SaleApiController::class, 'destroy']);

    // ---- Purchases ----
    Route::get('/purchases', [PurchaseApiController::class, 'index']);
    Route::get('/purchases/{id}', [PurchaseApiController::class, 'show']);
    Route::post('/purchases', [PurchaseApiController::class, 'store']);
    Route::put('/purchases/{id}', [PurchaseApiController::class, 'update']);
    Route::delete('/purchases/{id}', [PurchaseApiController::class, 'destroy']);

    // ---- Payment receipts ----
    Route::get('/payment-receipts', [PaymentReceiptApiController::class, 'index']);
    Route::get('/payment-receipts/{id}', [PaymentReceiptApiController::class, 'show']);
    Route::post('/payment-receipts', [PaymentReceiptApiController::class, 'store']);
    Route::put('/payment-receipts/{id}', [PaymentReceiptApiController::class, 'update']);
    Route::delete('/payment-receipts/{id}', [PaymentReceiptApiController::class, 'destroy']);

    // ---- Users ----
    Route::get('/users', [UserApiController::class, 'index']);
    Route::get('/users/{id}', [UserApiController::class, 'show']);
    Route::post('/users', [UserApiController::class, 'store']);
    Route::put('/users/{id}', [UserApiController::class, 'update']);
    Route::delete('/users/{id}', [UserApiController::class, 'destroy']);
});
