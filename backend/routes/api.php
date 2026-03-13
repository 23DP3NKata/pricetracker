<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PriceHistoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/ping', function () {
    return response()->json(['status' => 'ok', 'time' => now()->toIso8601String()]);
});

Route::middleware(['auth:sanctum'])->group(function () {
    // User profile
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // User settings
    Route::get('/user/profile', [UserController::class, 'profile']);
    Route::put('/user/name', [UserController::class, 'updateName']);
    Route::put('/user/email', [UserController::class, 'updateEmail']);
    Route::put('/user/password', [UserController::class, 'updatePassword']);

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // Products CRUD
    Route::get('/products/supported-stores', [ProductController::class, 'supportedStores']);
    Route::apiResource('products', ProductController::class);

    // Price history for a product
    Route::get('/products/{product}/prices', [PriceHistoryController::class, 'index']);

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead']);
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead']);
});

require __DIR__.'/auth.php';
