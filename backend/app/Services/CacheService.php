<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class CacheService
{
    /**
     * Clear cache after product price update
     */
    public function clearProductCache($productId)
    {
        // Main product cache
        Cache::forget("product_{$productId}");
        
        // History cache for different periods
        $periods = [7, 30, 90, 365, null];
        foreach ($periods as $days) {
            $cacheKey = "price_data_{$productId}_days_{$days}";
            Cache::forget($cacheKey);
            
            // And cache pages for history
            for ($page = 1; $page <= 10; $page++) {
                Cache::forget("price_history_{$productId}_days_{$days}_page_{$page}");
            }
        }
        
        // Clear top-assets cache
        for ($limit = 1; $limit <= 20; $limit++) {
            Cache::forget("top_assets_limit_{$limit}");
        }
    }

    /**
     * Clear user dashboard cache
     */
    public function clearUserDashboard($userId)
    {
        Cache::forget("dashboard_{$userId}");
    }

    /**
     * Clear user notifications cache
     */
    public function clearUserNotifications($userId)
    {
        Cache::forget("unread_count_{$userId}");
        
        // Clear cached pages of notifications
        for ($page = 1; $page <= 10; $page++) {
            Cache::forget("notifications_{$userId}_page_{$page}");
        }
    }
}
