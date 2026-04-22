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

        $firstCheckAt = PriceHistory::query()->min('checked_at');

        $systemStart = Carbon::parse(env('SYSTEM_START_DATE', now()));

        $requestsDay = $this->expectedCyclesSince(max($startOfDay, $systemStart), $now);
        $requestsMonth = $this->expectedCyclesSince(max($startOfMonth, $systemStart), $now);
        $requestsAllTime = 0;

        if ($firstCheckAt && Carbon::parse($firstCheckAt)->gte($systemStart)) {
            $requestsAllTime = $this->expectedCyclesSince($firstCheckAt, $now);
        }

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

    private function expectedCyclesSince($startAt, $endAt): int
    {
        $start = $startAt instanceof CarbonInterface ? $startAt->copy() : Carbon::parse($startAt);
        $end = $endAt instanceof CarbonInterface ? $endAt->copy() : Carbon::parse($endAt);

        if ($start->gt($end)) {
            return 0;
        }

        $minutes = $start->diffInMinutes($end);

        return intdiv(max(0, $minutes), self::TRACKING_INTERVAL_MINUTES) + 1;
    }
}
