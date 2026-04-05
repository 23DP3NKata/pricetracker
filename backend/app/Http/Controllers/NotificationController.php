<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * List notifications for the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $notifications = Notification::where('user_id', $request->user()->id)
            ->with('product:id,title,symbol,image_url,product_page_url')
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json($notifications);
    }

    /**
     * Get count of unread notifications.
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $count = Notification::where('user_id', $request->user()->id)
            ->where('is_read', false)
            ->count();

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

        return response()->json(['message' => 'Marked as read.']);
    }

    /**
     * Mark all notifications as read for the authenticated user.
     */
    public function markAllRead(Request $request): JsonResponse
    {
        Notification::where('user_id', $request->user()->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json(['message' => 'All notifications marked as read.']);
    }
}
