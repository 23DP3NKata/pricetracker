<?php

namespace App\Http\Controllers;

use App\Models\PriceHistory;
use App\Models\Product;
use App\Models\User;
use App\Models\UserProduct;
use App\Services\CoinGeckoPriceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    private const TRACKING_INTERVAL_MINUTES = 5;

    public function __construct(
        protected CoinGeckoPriceService $priceService,
    ) {}

    /**
     * Public top assets feed for homepage cards.
     */
    public function topAssets(Request $request): JsonResponse
    {
        $limit = max(1, min((int) $request->integer('limit', 10), 20));

        $assets = Product::query()
            ->where('status', 'active')
            ->whereNotNull('symbol')
            ->whereNotNull('current_price')
            ->orderByDesc('tracking_count')
            ->orderByDesc('checks_count')
            ->limit($limit)
            ->get([
                'id',
                'title',
                'symbol',
                'image_url',
                'current_price',
                'price_change_24h',
                'trend',
                'currency',
                'tracking_count',
                'product_page_url',
            ]);

        if ($assets->isEmpty()) {
            return response()->json(['data' => []]);
        }

        $assetIds = $assets->pluck('id')->all();

        $historyByAsset = PriceHistory::query()
            ->whereIn('product_id', $assetIds)
            ->where('checked_at', '>=', now()->subDays(7))
            ->orderBy('checked_at')
            ->get(['product_id', 'price', 'checked_at'])
            ->groupBy('product_id');

        $trackedMap = [];
        if ($request->user()) {
            $trackedMap = UserProduct::query()
                ->where('user_id', $request->user()->id)
                ->whereIn('product_id', $assetIds)
                ->pluck('id', 'product_id')
                ->map(fn() => true)
                ->all();
        }

        $data = $assets->map(function (Product $asset) use ($historyByAsset, $trackedMap) {
            $points = collect($historyByAsset->get($asset->id, []))
                ->take(-48)
                ->map(fn($row) => [
                    'price' => (float) $row->price,
                    'checked_at' => optional($row->checked_at)->toDateTimeString(),
                ])
                ->values();

            return [
                'id' => $asset->id,
                'title' => $asset->title,
                'symbol' => $asset->symbol,
                'image_url' => $asset->image_url,
                'current_price' => $asset->current_price,
                'price_change_24h' => $asset->price_change_24h,
                'trend' => $asset->trend,
                'currency' => $asset->currency,
                'tracking_count' => $asset->tracking_count,
                'product_page_url' => $asset->product_page_url,
                'is_tracked' => (bool) ($trackedMap[$asset->id] ?? false),
                'history' => $points,
            ];
        })->values();

        return response()->json(['data' => $data]);
    }

    /**
     * List assets tracked by authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'sort_by' => ['nullable', Rule::in(['created_at', 'title', 'symbol', 'current_price', 'next_check_at', 'last_checked_at'])],
            'sort_dir' => ['nullable', Rule::in(['asc', 'desc'])],
            'symbol' => ['nullable', 'string', 'max:20'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $sortMap = [
            'created_at' => 'user_products.created_at',
            'title' => 'products.title',
            'symbol' => 'products.symbol',
            'current_price' => 'products.current_price',
            'next_check_at' => 'user_products.next_check_at',
            'last_checked_at' => 'user_products.last_checked_at',
        ];

        $sortBy = $validated['sort_by'] ?? 'created_at';
        $sortDir = $validated['sort_dir'] ?? 'desc';
        $sortColumn = $sortMap[$sortBy] ?? 'user_products.created_at';
        $symbolFilter = strtoupper(trim((string) ($validated['symbol'] ?? '')));
        $perPage = (int) ($validated['per_page'] ?? 20);

        $query = $request->user()
            ->products()
            ->where('products.status', 'active')
            ->withPivot('check_interval', 'target_price', 'notify_when', 'is_active', 'last_checked_at', 'next_check_at', 'last_notified_at', 'created_at')
            ->orderBy($sortColumn, $sortDir);

        if ($symbolFilter !== '') {
            $query->where('products.symbol', $symbolFilter);
        }

        return response()->json($query->paginate($perPage));
    }

    /**
     * Add an asset to tracking by ticker symbol (e.g., BTC, ETH, LTC).
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'symbol' => ['required', 'string', 'max:20', 'regex:/^[A-Za-z0-9._-]+$/'],
            'target_price' => ['nullable', 'numeric', 'min:0.00000001'],
            'notify_when' => ['nullable', Rule::in(['below', 'above'])],
        ]);

        $authUser = $request->user();

        if (!$authUser->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Email verification required before adding assets.',
            ], 403);
        }

        $symbol = strtoupper(trim($validated['symbol']));

        $product = Product::query()
            ->where('symbol', $symbol)
            ->first();

        if (!$product) {
            try {
                $details = $this->priceService->fetchAssetDetails($symbol);
            } catch (\Throwable $e) {
                return response()->json([
                    'message' => $e->getMessage(),
                ], 422);
            }

            $product = Product::firstOrCreate(
                ['coingecko_id' => $details['coingecko_id']],
                [
                    'title' => $details['title'],
                    'symbol' => $details['symbol'],
                    'coingecko_id' => $details['coingecko_id'],
                    'product_page_url' => $details['product_page_url'],
                    'image_url' => $details['image_url'],
                    'current_price' => $details['current_price'],
                    'currency' => $details['currency'],
                ]
            );

            if (!$product->symbol || !$product->coingecko_id) {
                $product->update([
                    'title' => $details['title'],
                    'symbol' => $details['symbol'],
                    'coingecko_id' => $details['coingecko_id'],
                    'product_page_url' => $details['product_page_url'],
                    'image_url' => $details['image_url'],
                    'current_price' => $details['current_price'],
                    'currency' => $details['currency'],
                ]);
            }
        }

        if (!PriceHistory::where('product_id', $product->id)->exists() && $product->current_price !== null) {
            PriceHistory::create([
                'product_id' => $product->id,
                'price' => $product->current_price,
                'checked_at' => now(),
            ]);
        }

        $checkInterval = self::TRACKING_INTERVAL_MINUTES;

        $createResult = DB::transaction(function () use ($authUser, $product, $checkInterval, $validated) {
            $user = User::query()->lockForUpdate()->findOrFail($authUser->id);

            if ($user->checks_used >= $user->monthly_limit) {
                return ['created' => false, 'reason' => 'limit'];
            }

            $tracking = UserProduct::query()->firstOrCreate(
                [
                    'user_id' => $user->id,
                    'product_id' => $product->id,
                ],
                [
                    'check_interval' => $checkInterval,
                    'target_price' => $validated['target_price'] ?? null,
                    'notify_when' => $validated['notify_when'] ?? 'below',
                    'next_check_at' => now()->addMinutes($checkInterval),
                ]
            );

            if (!$tracking->wasRecentlyCreated) {
                return ['created' => false, 'reason' => 'exists'];
            }

            $product->increment('tracking_count');
            $user->increment('checks_used');

            return ['created' => true, 'reason' => null];
        });

        if (!$createResult['created']) {
            if ($createResult['reason'] === 'limit') {
                return response()->json([
                    'message' => 'Monthly request limit reached.',
                ], 403);
            }

            return response()->json(['message' => 'You are already tracking this asset.'], 409);
        }

        return response()->json([
            'message' => 'Asset added to tracking.',
            'product' => $product->fresh(),
        ], 201);
    }

    /**
     * Show single tracked asset with pivot data.
     */
    public function show(Request $request, Product $product): JsonResponse
    {
        $pivot = UserProduct::where('user_id', $request->user()->id)
            ->where('product_id', $product->id)
            ->first();

        if (!$pivot) {
            return response()->json(['message' => 'Asset not found in your tracking list.'], 404);
        }

        $product->load('priceHistory');
        $product->setAttribute('tracking', $pivot);

        return response()->json($product);
    }

    /**
     * Return current cached price from DB without external API calls.
     */
    public function currentPrice(Request $request, Product $product): JsonResponse
    {
        $tracking = UserProduct::where('user_id', $request->user()->id)
            ->where('product_id', $product->id)
            ->first();

        if (!$tracking) {
            return response()->json(['message' => 'Asset not found in your tracking list.'], 404);
        }

        $latestHistory = PriceHistory::query()
            ->where('product_id', $product->id)
            ->orderByDesc('checked_at')
            ->first();

        return response()->json([
            'product_id' => $product->id,
            'symbol' => $product->symbol,
            'price' => $product->current_price,
            'currency' => $product->currency,
            'price_change_24h' => $product->price_change_24h,
            'trend' => $product->trend,
            'checked_at' => optional($latestHistory?->checked_at)->toDateTimeString() ?: optional($product->last_successful_check)->toDateTimeString(),
            'next_check_at' => optional($tracking->next_check_at)->toDateTimeString(),
        ]);
    }

    /**
     * Update tracker settings.
     */
    public function update(Request $request, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'is_active' => ['sometimes', 'boolean'],
            'target_price' => ['nullable', 'numeric', 'min:0.00000001'],
            'notify_when' => ['sometimes', Rule::in(['below', 'above'])],
        ]);

        $pivot = UserProduct::where('user_id', $request->user()->id)
            ->where('product_id', $product->id)
            ->first();

        if (!$pivot) {
            return response()->json(['message' => 'Asset not found in your tracking list.'], 404);
        }

        $updates = $validated;

        $hasActiveUpdate = array_key_exists('is_active', $validated);
        $effectiveActive = $hasActiveUpdate ? (bool) $validated['is_active'] : (bool) $pivot->is_active;

        if ($hasActiveUpdate) {
            $updates['check_interval'] = self::TRACKING_INTERVAL_MINUTES;
            $updates['next_check_at'] = $effectiveActive
                ? now()->addMinutes(self::TRACKING_INTERVAL_MINUTES)
                : null;
        }

        if (array_key_exists('target_price', $validated) && $validated['target_price'] === null) {
            $updates['last_notified_at'] = null;
        }

        $pivot->update($updates);
        $pivot->refresh();

        return response()->json([
            'message' => 'Tracking settings updated.',
            'tracking' => $pivot,
        ]);
    }

    /**
     * Alias endpoint dedicated to alert setup.
     */
    public function updateAlerts(Request $request, Product $product): JsonResponse
    {
        return $this->update($request, $product);
    }

    /**
     * Remove an asset from user's tracking list.
     */
    public function destroy(Request $request, Product $product): JsonResponse
    {
        $deleted = UserProduct::where('user_id', $request->user()->id)
            ->where('product_id', $product->id)
            ->delete();

        if (!$deleted) {
            return response()->json(['message' => 'Asset not found in your tracking list.'], 404);
        }

        $product->decrement('tracking_count');

        return response()->json(null, 204);
    }
}
