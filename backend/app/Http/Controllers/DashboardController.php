<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\Product;
use App\Models\UserProduct;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Return dashboard statistics for the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        // Total tracked products
        $totalProducts = UserProduct::where('user_id', $userId)
            ->where('is_active', true)
            ->count();

        // Unread notifications count
        $unreadNotifications = Notification::where('user_id', $userId)
            ->where('is_read', false)
            ->count();

        // Recent price drops (last 7 days) — notifications where new_price < old_price
        $recentDrops = Notification::where('user_id', $userId)
            ->where('created_at', '>=', now()->subDays(7))
            ->whereColumn('new_price', '<', 'old_price')
            ->count();

        // Recent price increases (last 7 days)
        $recentIncreases = Notification::where('user_id', $userId)
            ->where('created_at', '>=', now()->subDays(7))
            ->whereColumn('new_price', '>', 'old_price')
            ->count();

        // Monthly checks usage
        $user = $request->user();

        // Top 5 biggest recent drops
        $topDrops = Notification::where('user_id', $userId)
            ->where('created_at', '>=', now()->subDays(7))
            ->whereColumn('new_price', '<', 'old_price')
            ->with('product:id,title,url,image_url,store_name,currency')
            ->orderByRaw('(old_price - new_price) DESC')
            ->limit(5)
            ->get();

        return response()->json([
            'total_products' => $totalProducts,
            'unread_notifications' => $unreadNotifications,
            'recent_drops' => $recentDrops,
            'recent_increases' => $recentIncreases,
            'monthly_limit' => $user->monthly_limit,
            'checks_used' => $user->checks_used,
            'top_drops' => $topDrops,
        ]);
    }
}
