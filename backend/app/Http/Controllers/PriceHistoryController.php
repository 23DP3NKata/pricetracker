<?php

namespace App\Http\Controllers;

use App\Models\PriceHistory;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

        $historyQuery = PriceHistory::where('product_id', $product->id)
            ->orderByDesc('checked_at');

        $statsQuery = PriceHistory::where('product_id', $product->id);
        $chartQuery = PriceHistory::where('product_id', $product->id)
            ->orderBy('checked_at');

        if ($days !== null) {
            $historyQuery->where('checked_at', '>=', now()->subDays($days));
            $statsQuery->where('checked_at', '>=', now()->subDays($days));
            $chartQuery->where('checked_at', '>=', now()->subDays($days));
        }

        $history = $historyQuery->paginate(10, ['id', 'price', 'checked_at']);
        $historyRows = $history->items();

        if (empty($historyRows) && $product->current_price !== null && (int) $history->currentPage() === 1) {
            $historyRows = [[
                'id' => null,
                'price' => $product->current_price,
                'checked_at' => $product->last_successful_check ?? $product->created_at,
            ]];
        }

        $chartRows = $chartQuery->get(['id', 'price', 'checked_at']);
        if ($chartRows->isEmpty() && $product->current_price !== null) {
            $chartRows = [[
                'id' => null,
                'price' => $product->current_price,
                'checked_at' => $product->last_successful_check ?? $product->created_at,
            ]];
        }

        $statsRaw = $statsQuery
            ->selectRaw('MIN(price) as min_price, MAX(price) as max_price, AVG(price) as avg_price, COUNT(*) as data_points')
            ->first();

        $statsMin = null;
        $statsMax = null;
        $statsAvg = null;
        $statsDataPoints = 0;

        if ($statsRaw && (int) $statsRaw->data_points > 0) {
            $statsMin = $statsRaw->min_price;
            $statsMax = $statsRaw->max_price;
            $statsAvg = round((float) $statsRaw->avg_price, 2);
            $statsDataPoints = (int) $statsRaw->data_points;
        } elseif ($product->current_price !== null) {
            $statsMin = $product->current_price;
            $statsMax = $product->current_price;
            $statsAvg = round((float) $product->current_price, 2);
            $statsDataPoints = 1;
        }

        $stats = [
            'min' => $statsMin,
            'max' => $statsMax,
            'avg' => $statsAvg,
            'current' => $product->current_price,
            'data_points' => $statsDataPoints,
        ];

        return response()->json([
            'product_id' => $product->id,
            'symbol' => $product->symbol,
            'period_days' => $days,
            'stats' => $stats,
            'history' => $historyRows,
            'chart_history' => $chartRows,
            'pagination' => [
                'current_page' => (int) $history->currentPage(),
                'last_page' => (int) $history->lastPage(),
                'per_page' => (int) $history->perPage(),
                'total' => (int) $history->total(),
            ],
        ]);
    }
}
