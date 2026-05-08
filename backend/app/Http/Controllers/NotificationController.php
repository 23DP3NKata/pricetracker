<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class NotificationController extends Controller
{
    /**
     * List notifications for the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $page = (int) $request->query('page', 1);
        $cacheKey = "notifications_{$userId}_page_{$page}";

        $cached = Cache::get($cacheKey);
        if ($cached) {
            return response()->json($cached);
        }

        $notifications = Notification::where('user_id', $userId)
            ->with('product:id,title,symbol,image_url,product_page_url')
            ->orderByDesc('created_at')
            ->paginate(20);

        Cache::put($cacheKey, $notifications, 120); // 2 minutes

        return response()->json($notifications);
    }

    /**
     * Get count of unread notifications.
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $cacheKey = "unread_count_{$userId}";

        $count = Cache::remember($cacheKey, 30, function () use ($userId) {
            return Notification::where('user_id', $userId)
                ->where('is_read', false)
                ->count();
        });

        return response()->json(['unread_count' => $count]);
    }

    /**
     * Mark a single notification as read.
     */
    public function markRead(Request $request, Notification $notification): JsonResponse
    {
        if ($notification->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        $notification->update(['is_read' => true]);

        // Clear cache
        Cache::forget("unread_count_{$notification->user_id}");
        Cache::forget("notifications_{$notification->user_id}_page_1");

        return response()->json(['message' => 'Marked as read.']);
    }

    /**
     * Mark all notifications as read for the authenticated user.
     */
    public function markAllRead(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        Notification::where('user_id', $userId)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        // Clear cache
        Cache::forget("unread_count_{$userId}");
        for ($i = 1; $i <= 10; $i++) { // Clear several pages
            Cache::forget("notifications_{$userId}_page_{$i}");
        }

        return response()->json(['message' => 'All notifications marked as read.']);
    }
}
