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
            'days' => ['sometimes', 'integer', 'min:1', 'max:365'],
        ]);

        $days = $validated['days'] ?? 30;

        $history = PriceHistory::where('product_id', $product->id)
            ->where('checked_at', '>=', now()->subDays($days))
            ->orderBy('checked_at')
            ->get(['id', 'price', 'checked_at']);

        if ($history->isEmpty() && $product->current_price !== null) {
            $history = collect([[
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
