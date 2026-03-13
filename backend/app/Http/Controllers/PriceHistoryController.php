<?php

namespace App\Http\Controllers;

use App\Models\PriceHistory;
use App\Models\Product;
use App\Models\UserProduct;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PriceHistoryController extends Controller
{
    /**
     * Get price history for a product the user is tracking.
     */
    public function index(Request $request, Product $product): JsonResponse
    {
        // Ensure the user is tracking this product
        $tracking = UserProduct::where('user_id', $request->user()->id)
            ->where('product_id', $product->id)
            ->exists();

        if (!$tracking) {
            return response()->json(['message' => 'Product not found in your tracking list.'], 404);
        }

        $validated = $request->validate([
            'days' => ['sometimes', 'integer', 'min:1', 'max:365'],
        ]);

        $days = $validated['days'] ?? 30;

        $history = PriceHistory::where('product_id', $product->id)
            ->where('checked_at', '>=', now()->subDays($days))
            ->orderBy('checked_at')
            ->get(['id', 'price', 'checked_at']);

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
            'period_days' => $days,
            'stats' => $stats,
            'history' => $history,
        ]);
    }
}
