<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAction;
use App\Models\Notification;
use App\Models\Product;
use App\Models\SystemLog;
use App\Models\User;
use App\Models\UserProduct;
use Illuminate\Http\JsonResponse;

class AdminDashboardController extends Controller
{
    public function index(): JsonResponse
    {
        $now = now();
        $last24h = $now->copy()->subDay();
        $last7d = $now->copy()->subDays(7);

        $usersTotal = User::count();
        $adminsTotal = User::where('role', 'admin')->count();
        $activeUsers = User::where('status', 'active')->count();
        $blockedUsers = User::where('status', 'blocked')->count();

        $productsTotal = Product::whereIn('status', ['active', 'hidden'])->count();
        $productsActive = Product::where('status', 'active')->count();
        $productsHidden = Product::where('status', 'hidden')->count();

        $activeTrackingLinks = UserProduct::where('is_active', true)->count();

        $logs24h = SystemLog::where('created_at', '>=', $last24h)->count();
        $errors24h = SystemLog::where('created_at', '>=', $last24h)
            ->whereIn('level', ['error', 'critical'])
            ->count();

        $actions7d = AdminAction::where('created_at', '>=', $last7d)->count();
        $notifications24h = Notification::where('created_at', '>=', $last24h)->count();

        return response()->json([
            'users_total' => $usersTotal,
            'admins_total' => $adminsTotal,
            'active_users' => $activeUsers,
            'blocked_users' => $blockedUsers,

            'products_total' => $productsTotal,
            'products_active' => $productsActive,
            'products_hidden' => $productsHidden,
            'active_tracking_links' => $activeTrackingLinks,

            'logs_24h' => $logs24h,
            'errors_24h' => $errors24h,
            'actions_7d' => $actions7d,
            'notifications_24h' => $notifications24h,
        ]);
    }
}
