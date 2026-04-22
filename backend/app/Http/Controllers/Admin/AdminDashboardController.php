<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAction;
use App\Models\Notification;
use App\Models\PriceHistory;
use App\Models\Product;
use App\Models\SystemLog;
use App\Models\User;
use App\Models\UserProduct;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class AdminDashboardController extends Controller
{
    private const TRACKING_INTERVAL_MINUTES = 5;

    public function index(): JsonResponse
    {
        $now = now();
        $startOfDay = $now->copy()->startOfDay();
        $startOfMonth = $now->copy()->startOfMonth();
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

        $requestsDay   = (int) Cache::get('api_requests_day_' . $now->format('Y-m-d'), 0);
        $requestsMonth = (int) Cache::get('api_requests_month_' . $now->format('Y-m'), 0);
        $requestsAllTime = (int) Cache::get('api_requests_total', 0);

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
            'requests_day' => $requestsDay,
            'requests_month' => $requestsMonth,
            'requests_all_time' => $requestsAllTime,
        ]);
    }
}
