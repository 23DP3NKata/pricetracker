<?php

namespace App\Http\Controllers;

use App\Models\PriceHistory;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PriceHistoryController extends Controller
{
    /**
     * Get price history for a product.
     */
    public function index(Request $request, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'days' => ['nullable', 'integer', 'min:1', 'max:3650'],
        ]);

        $days = $validated['days'] ?? null;
        $page = (int) $request->query('page', 1);

        // Cache for 5 minutes; key includes period and page
        $cacheKey = "price_history_{$product->id}_days_{$days}_page_{$page}";
        $cacheTtl = 300; // 5 minutes

        $cachedData = Cache::get($cacheKey);
        if ($cachedData) {
            return response()->json($cachedData);
        }

        // Retrieve all data for the period
        $allHistory = Cache::remember("price_data_{$product->id}_days_{$days}", 240, function () use ($product, $days) {
            $query = PriceHistory::where('product_id', $product->id);
            
            if ($days !== null) {
                $query->where('checked_at', '>=', now()->subDays($days));
            }
            
            return $query->orderBy('checked_at')->get(['id', 'price', 'checked_at']);
        });

        // Table - reverse order with pagination
        $perPage = 10;
        $reversed = $allHistory->reverse()->values();
        $total = $reversed->count();
        $lastPage = max(1, ceil($total / $perPage));

        $historyRows = $reversed
            ->slice(($page - 1) * $perPage, $perPage)
            ->values()
            ->all();

        if (empty($historyRows) && $product->current_price !== null && $page === 1) {
            $historyRows = [[
                'id' => null,
                'price' => $product->current_price,
                'checked_at' => $product->last_successful_check ?? $product->created_at,
            ]];
        }

        // Chart - in chronological order
        $chartRows = $allHistory->all();
        if (empty($chartRows) && $product->current_price !== null) {
            $chartRows = [[
                'id' => null,
                'price' => $product->current_price,
                'checked_at' => $product->last_successful_check ?? $product->created_at,
            ]];
        }

        // Statistics
        $stats = $this->calculateStats($allHistory, $product);

        $result = [
            'product_id' => $product->id,
            'symbol' => $product->symbol,
            'period_days' => $days,
            'stats' => $stats,
            'history' => $historyRows,
            'chart_history' => $chartRows,
            'pagination' => [
                'current_page' => $page,
                'last_page' => $lastPage,
                'per_page' => $perPage,
                'total' => $total,
            ],
        ];

        Cache::put($cacheKey, $result, $cacheTtl);

        return response()->json($result);
    }

    private function calculateStats($historyCollection, $product)
    {
        if ($historyCollection->isEmpty()) {
            if ($product->current_price !== null) {
                return [
                    'min' => $product->current_price,
                    'max' => $product->current_price,
                    'avg' => round((float) $product->current_price, 2),
                    'current' => $product->current_price,
                    'data_points' => 1,
                ];
            }

            return [
                'min' => null,
                'max' => null,
                'avg' => null,
                'current' => null,
                'data_points' => 0,
            ];
        }

        $prices = $historyCollection->pluck('price')->map(fn($p) => (float) $p);
        $min = $prices->min();
        $max = $prices->max();
        $avg = round($prices->avg(), 2);

        return [
            'min' => $min,
            'max' => $max,
            'avg' => $avg,
            'current' => $product->current_price,
            'data_points' => $historyCollection->count(),
        ];
    }
}
