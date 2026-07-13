<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\PaymentReceiptController;

Route::get('/', function () {
    return view('welcome')->name('home');
});

// Serve uploaded files through Laravel (works even when the host blocks the
// public/storage symlink → fixes 403 Forbidden on images on shared hosting).
Route::get('storage/{path}', function (string $path) {
    abort_unless(Storage::disk('public')->exists($path), 404);
    return response()->file(Storage::disk('public')->path($path));
})->where('path', '.*');

Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');


Route::middleware('auth')->group(function () {
    Route::get('products/alerts', [productController::class, 'alerts'])->name('products.alerts');

        Route::get('/sales/search-products', [SaleController::class, 'searchProducts'])->name('sales.search-products');

    Route::resource('/sales', SaleController::class);

    Route::resource('/purchases', PurchaseController::class);

    Route::resource('/products', ProductController::class);

    Route::resource('/payment_receipts', PaymentReceiptController::class);

    Route::resource('/users', UserController::class);

    Route::get('/scan', function () {
        return view('scan');
    });

    Route::post('/scan-product', [ProductController::class, 'scanProduct']);

    Route::get('/products/by-barcode/{barcode}', [ProductController::class, 'getByBarcode'])->name('products.byBarcode');

});

