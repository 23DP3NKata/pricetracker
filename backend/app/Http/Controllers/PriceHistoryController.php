<?php

namespace App\Http\Controllers;

use App\Models\PriceHistory;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

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
            ->orderBy('checked_at');

        if ($days !== null) {
            $historyQuery->where('checked_at', '>=', now()->subDays($days));
        }

        $history = $historyQuery->get(['id', 'price', 'checked_at']);

        if ($history->isEmpty() && $product->current_price !== null) {
            $history = new Collection([[
                'id' => null,
                'price' => $product->current_price,
                'checked_at' => $product->last_successful_check ?? $product->created_at,
            ]]);
        }

        // Compute stats
        $prices = $history->pluck('price');
        $stats = [
            'min' => $prices->min(),
            'max' => $prices->max(),
            'avg' => round($prices->avg(), 2),
            'current' => $product->current_price,
            'data_points' => $history->count(),
        ];

        return response()->json([
            'product_id' => $product->id,
            'symbol' => $product->symbol,
            'period_days' => $days,
            'stats' => $stats,
            'history' => $history,
        ]);
    }
}
