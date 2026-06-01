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
        $page = max(1, (int) $request->query('page', 1));
        $perPage = 10;
        $chartLimit = 2000;

        // Cache for 5 minutes; key includes period and page
        $cacheKey = "price_history_{$product->id}_days_{$days}_page_{$page}";
        $cacheTtl = 300; // 5 minutes

        $cachedData = Cache::get($cacheKey);
        if ($cachedData) {
            return response()->json($cachedData);
        }

        $baseQuery = PriceHistory::query()->where('product_id', $product->id);
        if ($days !== null) {
            $baseQuery->where('checked_at', '>=', now()->subDays($days));
        }

        $historyPaginator = (clone $baseQuery)
            ->orderByDesc('checked_at')
            ->paginate($perPage, ['id', 'price', 'checked_at'], 'page', $page);

        $historyRows = $historyPaginator->items();

        if (empty($historyRows) && $product->current_price !== null && $page === 1) {
            $historyRows = [[
                'id' => null,
                'price' => $product->current_price,
                'checked_at' => $product->last_successful_check ?? $product->created_at,
            ]];
        }

        // Chart - in chronological order (limit rows to keep response fast)
        $chartRows = Cache::remember("price_history_chart_{$product->id}_days_{$days}", $cacheTtl, function () use ($baseQuery, $chartLimit) {
            $total = (clone $baseQuery)->count();
            if ($total <= $chartLimit) {
                return (clone $baseQuery)
                    ->orderBy('checked_at')
                    ->get(['id', 'price', 'checked_at'])
                    ->all();
            }

            $step = max(1, (int) ceil($total / $chartLimit));
            $rows = [];
            $index = 0;

            (clone $baseQuery)
                ->orderBy('checked_at')
                ->chunk(1000, function ($chunk) use (&$rows, &$index, $step) {
                    foreach ($chunk as $row) {
                        if ($index % $step === 0) {
                            $rows[] = $row;
                        }
                        $index += 1;
                    }
                });

            return $rows;
        });
        if (empty($chartRows) && $product->current_price !== null) {
            $chartRows = [[
                'id' => null,
                'price' => $product->current_price,
                'checked_at' => $product->last_successful_check ?? $product->created_at,
            ]];
        }

        // Statistics
        $stats = Cache::remember("price_history_stats_{$product->id}_days_{$days}", $cacheTtl, function () use ($baseQuery, $product) {
            $statsRow = (clone $baseQuery)
                ->selectRaw('MIN(price) as min, MAX(price) as max, AVG(price) as avg, COUNT(*) as data_points')
                ->first();

            $dataPoints = (int) ($statsRow->data_points ?? 0);
            if ($dataPoints <= 0) {
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

            return [
                'min' => $statsRow->min !== null ? (float) $statsRow->min : null,
                'max' => $statsRow->max !== null ? (float) $statsRow->max : null,
                'avg' => $statsRow->avg !== null ? round((float) $statsRow->avg, 2) : null,
                'current' => $product->current_price,
                'data_points' => $dataPoints,
            ];
        });

        $result = [
            'product_id' => $product->id,
            'symbol' => $product->symbol,
            'period_days' => $days,
            'stats' => $stats,
            'history' => $historyRows,
            'chart_history' => $chartRows,
            'pagination' => [
                'current_page' => $historyPaginator->currentPage(),
                'last_page' => $historyPaginator->lastPage(),
                'per_page' => $historyPaginator->perPage(),
                'total' => $historyPaginator->total(),
            ],
        ];

        Cache::put($cacheKey, $result, $cacheTtl);

        return response()->json($result);
    }
}
