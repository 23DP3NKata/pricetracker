<?php

use App\Http\Controllers\Admin\AdminActionController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminLogController;
use App\Http\Controllers\Admin\AdminProductController;
use App\Http\Controllers\Admin\AdminUserController;
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

Route::get('/market/top-assets', [ProductController::class, 'topAssets']);

Route::middleware(['auth:sanctum'])->group(function () {
    // User profile
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::middleware('active')->group(function () {
        // User settings
        Route::get('/user/profile', [UserController::class, 'profile']);
        Route::put('/user/name', [UserController::class, 'updateName']);
        Route::put('/user/email', [UserController::class, 'updateEmail']);
        Route::put('/user/password', [UserController::class, 'updatePassword']);

        // Dashboard
        Route::get('/dashboard', [DashboardController::class, 'index']);

        // Products CRUD
        Route::apiResource('products', ProductController::class);

        // Price history for a product
        Route::get('/products/{product}/prices', [PriceHistoryController::class, 'index']);

        // Asset-style aliases (crypto tracking)
        Route::post('/assets', [ProductController::class, 'store']);
        Route::get('/assets/{product}/current-price', [ProductController::class, 'currentPrice']);
        Route::get('/assets/{product}/history', [PriceHistoryController::class, 'index']);
        Route::patch('/assets/{product}/alerts', [ProductController::class, 'updateAlerts']);

        // Notifications
        Route::get('/notifications', [NotificationController::class, 'index']);
        Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
        Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead']);
        Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead']);

        // Admin APIs
        Route::middleware('admin')->prefix('admin')->group(function () {
            Route::get('/dashboard', [AdminDashboardController::class, 'index']);

            Route::get('/users', [AdminUserController::class, 'index']);
            Route::patch('/users/{user}/status', [AdminUserController::class, 'updateStatus']);
            Route::patch('/users/{user}/limit', [AdminUserController::class, 'updateLimit']);
            Route::patch('/users/{user}/role', [AdminUserController::class, 'updateRole']);

            Route::get('/products', [AdminProductController::class, 'index']);
            Route::patch('/products/{product}/status', [AdminProductController::class, 'updateStatus']);

            Route::get('/logs', [AdminLogController::class, 'index']);
            Route::get('/logs/export', [AdminLogController::class, 'exportCsv']);

            Route::get('/actions', [AdminActionController::class, 'index']);
            Route::get('/actions/export', [AdminActionController::class, 'exportCsv']);
        });
    });
});

require __DIR__.'/auth.php';
