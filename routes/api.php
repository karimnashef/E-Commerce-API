<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\TelegramController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;

Route::prefix('v1')->group(function () {

    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->name('auth.forgot-password');
    Route::post('/telegram/webhook/', [TelegramController::class, 'webhook']);

    Route::middleware('auth:sanctum')->group(function () {

    // Authenticated routes

        Route::get('/profile', [AuthController::class, 'profile'])->name('auth.profile');
        Route::put('/profile', [AuthController::class, 'updateProfile'])->name('auth.update-profile');

        Route::post('/generate-security-key', [AuthController::class, 'generateSecurityKey'])->name('auth.generate-security-key');

        Route::post('/change-password', [AuthController::class, 'changePassword'])->name('auth.change-password');

        Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');

    // Categories routes
        Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
        Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
        Route::put('/categories/{id}', [CategoryController::class, 'update'])->name('categories.update');
        Route::delete('/categories/{id}', [CategoryController::class, 'destroy'])->name('categories.destroy');

    // Products routes
        Route::get('/products', [ProductController::class, 'index'])->name('products.index');
        Route::get('/products/all', [ProductController::class, 'showAll'])->name('products.show-all');
        Route::get('/products/{id}', [ProductController::class, 'show'])->name('products.show');
        Route::post('/products', [ProductController::class, 'store'])->name('products.store');
        Route::put('/products/{id}', [ProductController::class, 'update'])->name('products.update');
        Route::delete('/products/{id}', [ProductController::class, 'destroy'])->name('products.destroy');

    // Cart routes
        Route::post('/cart/save', [CartController::class, 'saveCart'])->name('cart.save ');
        Route::get('/cart', [CartController::class, 'getCart'])->name('cart.view');
        Route::post('/cart/remove', [CartController::class, 'removeCart'])->name('cart.remove');

    // Orders routes
       Route::prefix('orders')->group(function () {

            Route::get('/', [OrderController::class, 'myOrders']);
            Route::get('/received_orders', [OrderController::class, 'receivedOrders']);

            Route::post('/', [OrderController::class, 'store']);
            Route::delete('/{id}', [OrderController::class, 'destroy']);
            Route::put('/{id}', [OrderController::class, 'update']);

            Route::put('/{id}/canceled', [OrderController::class, 'cancel']);
            Route::put('/{id}/confirmed', [OrderController::class, 'confirm']);
            Route::put('/{id}/rejected', [OrderController::class, 'reject']);
            Route::put('/{id}/completed', [OrderController::class, 'complete']);

       });
    });
});

