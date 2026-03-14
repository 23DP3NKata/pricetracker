<?php

namespace App\Http\Controllers;

use App\Models\PriceHistory;
use App\Models\Product;
use App\Models\UserProduct;
use App\Services\PriceScraperService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct(
        protected PriceScraperService $scraper,
    ) {}

    /**
     * List products tracked by the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $products = $request->user()
            ->products()
            ->where('products.status', 'active')
            ->withPivot('check_interval', 'is_active', 'last_checked_at', 'next_check_at', 'created_at')
            ->orderByDesc('user_products.created_at')
            ->paginate(20);

        return response()->json($products);
    }

    /**
     * Add a product to tracking. User sends only the URL —
     * the system scrapes title, price, image, store name automatically.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'url' => ['required', 'url', 'max:500'],
            'check_interval' => ['sometimes', 'integer', 'min:30', 'max:44640'],
        ]);

        $url = $validated['url'];

        // Validate that the store is supported
        if (!$this->scraper->canHandle($url)) {
            $supported = implode(', ', array_keys($this->scraper->supportedStores()));
            return response()->json([
                'message' => "This store is not supported. Supported stores: {$supported}",
            ], 422);
        }

        $user = $request->user();

        // Check monthly limit
        if ($user->checks_used >= $user->monthly_limit) {
            return response()->json(['message' => 'Monthly tracking limit reached.'], 403);
        }

        // Normalize URL to canonical form
        try {
            $canonicalUrl = $this->scraper->normalizeUrl($url);
        } catch (\Throwable) {
            $canonicalUrl = $url;
        }

        // Check if user already tracks this product
        $existingProduct = Product::where('canonical_url', $canonicalUrl)
            ->orWhere(function ($query) use ($canonicalUrl) {
                $query->whereNull('canonical_url')->where('url', $canonicalUrl);
            })
            ->first();
        if ($existingProduct) {
            $alreadyTracking = UserProduct::where('user_id', $user->id)
                ->where('product_id', $existingProduct->id)
                ->exists();
            if ($alreadyTracking) {
                return response()->json(['message' => 'You are already tracking this product.'], 409);
            }
        }

        // Scrape product details from the page
        try {
            $details = $this->scraper->fetchProductDetails($url);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Failed to fetch product details: ' . $e->getMessage(),
            ], 422);
        }

        // Find or create the global product record
        $product = Product::firstOrCreate(
            ['canonical_url' => $details['canonical_url']],
            [
                'url' => $details['product_page_url'],
                'product_page_url' => $details['product_page_url'],
                'title' => $details['title'],
                'current_price' => $details['current_price'],
                'currency' => $details['currency'],
                'store_name' => $details['store_name'],
                'image_url' => $details['image_url'],
            ]
        );

        if (!$product->product_page_url) {
            $product->update(['product_page_url' => $details['product_page_url']]);
        }

        if (!$product->url) {
            $product->update(['url' => $details['product_page_url']]);
        }

        if (!PriceHistory::where('product_id', $product->id)->exists() && $product->current_price !== null) {
            PriceHistory::create([
                'product_id' => $product->id,
                'price' => $product->current_price,
                'checked_at' => now(),
            ]);
        }

        // Double-check user isn't already tracking (race condition guard)
        $exists = UserProduct::where('user_id', $user->id)
            ->where('product_id', $product->id)
            ->exists();
        if ($exists) {
            return response()->json(['message' => 'You are already tracking this product.'], 409);
        }

        // Create the user-product link
        $checkInterval = $validated['check_interval'] ?? 1440;
        UserProduct::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'check_interval' => $checkInterval,
            'next_check_at' => now()->addMinutes($checkInterval),
        ]);

        $product->increment('tracking_count');
        $user->increment('checks_used');

        return response()->json([
            'message' => 'Product added to tracking.',
            'product' => $product->fresh(),
        ], 201);
    }

    /**
     * List supported stores.
     */
    public function supportedStores(): JsonResponse
    {
        return response()->json([
            'stores' => $this->scraper->supportedStores(),
        ]);
    }

    /**
     * Show a single tracked product with pivot data.
     */
    public function show(Request $request, Product $product): JsonResponse
    {
        $pivot = UserProduct::where('user_id', $request->user()->id)
            ->where('product_id', $product->id)
            ->first();

        if (!$pivot) {
            return response()->json(['message' => 'Product not found in your tracking list.'], 404);
        }

        $product->load('priceHistory');
        $product->setAttribute('tracking', $pivot);

        return response()->json($product);
    }

    /**
     * Update tracking settings for a product.
     */
    public function update(Request $request, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'check_interval' => ['sometimes', 'integer', 'min:30', 'max:44640'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $pivot = UserProduct::where('user_id', $request->user()->id)
            ->where('product_id', $product->id)
            ->first();

        if (!$pivot) {
            return response()->json(['message' => 'Product not found in your tracking list.'], 404);
        }

        $pivot->update($validated);

        return response()->json(['message' => 'Tracking settings updated.', 'tracking' => $pivot]);
    }

    /**
     * Remove a product from the user's tracking list.
     */
    public function destroy(Request $request, Product $product): JsonResponse
    {
        $deleted = UserProduct::where('user_id', $request->user()->id)
            ->where('product_id', $product->id)
            ->delete();

        if (!$deleted) {
            return response()->json(['message' => 'Product not found in your tracking list.'], 404);
        }

        $product->decrement('tracking_count');

        return response()->json(null, 204);
    }
}
