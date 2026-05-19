<?php

namespace App\Http\Controllers;

use App\Models\PriceHistory;
use App\Models\Product;
use App\Models\User;
use App\Models\UserProduct;
use App\Services\CoinGeckoPriceService;
use App\Services\TrackingRuleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{

    public function __construct(
        protected CoinGeckoPriceService $priceService,
        protected TrackingRuleService $trackingRuleService,
    ) {}

    /**
     * Public top assets feed for homepage cards.
     */
    public function topAssets(Request $request): JsonResponse
    {
        $limit = max(1, min((int) $request->integer('limit', 10), 20));
        $cacheKey = "top_assets_limit_{$limit}";
        $cacheTtl = 300; // 5 minutes

        // Check cache
        $cached = Cache::get($cacheKey);
        if ($cached) {
            return response()->json($cached);
        }

        $assets = Product::query()
            ->where('status', 'active')
            ->whereNotNull('symbol')
            ->whereNotNull('current_price')
            ->orderByRaw('CASE WHEN `rank` IS NULL THEN 1 ELSE 0 END')
            ->orderBy('rank')
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
                'last_successful_check',
                'updated_at',
            ]);

        if ($assets->isEmpty()) {
            $result = ['data' => []];
            Cache::put($cacheKey, $result, $cacheTtl);
            return response()->json($result);
        }

        $assetIds = $assets->pluck('id')->all();

        // Retrieve all 7-day history in a single query
        $allHistory = PriceHistory::query()
            ->whereIn('product_id', $assetIds)
            ->where('checked_at', '>=', now()->subDays(7))
            ->orderBy('checked_at')
            ->get(['product_id', 'price', 'checked_at']);

        // Group by product_id
        $historyByAsset = [];
        foreach ($allHistory as $row) {
            if (!isset($historyByAsset[$row->product_id])) {
                $historyByAsset[$row->product_id] = [];
            }
            $historyByAsset[$row->product_id][] = $row;
        }

        // If user is authenticated - get tracked products
        $trackedMap = [];
        if ($request->user()) {
            $trackedIds = UserProduct::query()
                ->where('user_id', $request->user()->id)
                ->whereIn('product_id', $assetIds)
                ->where('is_active', true)
                ->whereNull('last_notified_at')
                ->pluck('id', 'product_id')
                ->all();

            foreach ($trackedIds as $productId => $unusedId) {
                $trackedMap[$productId] = true;
            }
        }

        // Assemble data
        $data = [];
        $lastUpdatedTs = null;
        $lastUpdatedAt = null;

        foreach ($assets as $asset) {
            $rows = $historyByAsset[$asset->id] ?? [];
            $rows = array_slice($rows, -48); // Take last 48 points

            $points = [];
            foreach ($rows as $row) {
                $points[] = [
                    'price' => (float) $row->price,
                    'checked_at' => optional($row->checked_at)->toDateTimeString(),
                ];
            }

            $lastUpdated = $asset->updated_at;
            if ($asset->last_successful_check) {
                $lastUpdated = $asset->last_successful_check;
            }

            if ($lastUpdated) {
                $ts = $lastUpdated->getTimestamp();
                if ($lastUpdatedTs === null || $ts > $lastUpdatedTs) {
                    $lastUpdatedTs = $ts;
                    $lastUpdatedAt = $lastUpdated;
                }
            }

            $data[] = [
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
                'last_updated_at' => optional($lastUpdated)?->toIso8601String(),
                'is_tracked' => (bool) ($trackedMap[$asset->id] ?? false),
                'history' => $points,
            ];
        }

        $result = [
            'data' => $data,
            'meta' => [
                'last_updated_at' => optional($lastUpdatedAt)?->toIso8601String(),
            ],
        ];

        Cache::put($cacheKey, $result, $cacheTtl);

        return response()->json($result);
    }

    /**
     * List assets tracked by authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'sort_by' => ['nullable', Rule::in(['created_at', 'title', 'symbol', 'current_price', 'last_checked_at'])],
            'sort_dir' => ['nullable', Rule::in(['asc', 'desc'])],
            'symbol' => ['nullable', 'string', 'max:20'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $sortMap = [
            'created_at' => 'user_products.created_at',
            'title' => 'products.title',
            'symbol' => 'products.symbol',
            'current_price' => 'products.current_price',
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
            ->withPivot('target_price', 'notify_when', 'is_active', 'last_checked_at', 'last_notified_at', 'created_at')
            ->orderBy($sortColumn, $sortDir);

        if ($symbolFilter !== '') {
            $query->where('products.symbol', $symbolFilter);
        }

        return response()->json($query->paginate($perPage));
    }

    /**
     * Add an asset to tracking by ticker symbol (BTC, ETH, LTC).
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

        $notifyWhen = $validated['notify_when'] ?? 'below';
        $targetPrice = null;
        if (array_key_exists('target_price', $validated) && $validated['target_price'] !== null) {
            $targetPrice = (float) $validated['target_price'];
        }

        $directionError = $this->validateTargetDirection($product, $targetPrice, $notifyWhen);
        if ($directionError) {
            return $directionError;
        }

        $createResult = DB::transaction(function () use ($authUser, $product, $notifyWhen, $targetPrice) {
            $user = User::query()->lockForUpdate()->findOrFail($authUser->id);

            if ($user->checks_used >= $user->monthly_limit) {
                return ['created' => false, 'reason' => 'limit'];
            }

            // Prevent duplicate tracking rules for the same user/product/price/direction
            $duplicateQuery = UserProduct::where('user_id', $user->id)
                ->where('product_id', $product->id)
                ->where('notify_when', $notifyWhen);

            if ($targetPrice === null) {
                $duplicateQuery->whereNull('target_price');
            } else {
                $duplicateQuery->where('target_price', $targetPrice);
            }

            if ($duplicateQuery->exists()) {
                return ['created' => false, 'reason' => 'duplicate'];
            }

            $tracking = UserProduct::query()->create([
                'user_id' => $user->id,
                'product_id' => $product->id,
                'target_price' => $targetPrice,
                'notify_when' => $notifyWhen,
            ]);

            $product->increment('tracking_count');
            $user->increment('checks_used');

            return ['created' => true, 'reason' => null, 'tracking' => $tracking];
        });

        if (!$createResult['created']) {
            if ($createResult['reason'] === 'limit') {
                return response()->json([
                    'message' => 'Monthly request limit reached.',
                ], 403);
            }

                if ($createResult['reason'] === 'duplicate') {
                    return response()->json([
                        'message' => 'Duplicate tracking rule already exists.',
                        'error_code' => 'duplicate_tracking',
                    ], 409);
            }

            return response()->json(['message' => 'Unable to create tracking rule.'], 409);
        }

        return response()->json([
            'message' => 'Asset added to tracking.',
            'product' => $product->fresh(),
            'tracking' => $createResult['tracking'],
        ], 201);
    }

    /**
     * List tracking rules for authenticated user.
     */
    public function trackingRules(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $perPage = (int) ($validated['per_page'] ?? 100);

        $ruleStatsByProductId = $this->trackingRuleService
            ->getRuleStatsByProductId($request->user()->id);

        $rules = UserProduct::query()
            ->where('user_id', $request->user()->id)
            ->with(['product' => function ($query) {
                $query->where('status', 'active');
            }])
            ->orderByDesc('created_at')
            ->paginate($perPage);

        $normalizedRules = [];
        foreach ($rules->getCollection() as $rule) {
            if (!$rule->product) {
                continue;
            }

            $normalizedRules[] = [
                'id' => $rule->id,
                'user_id' => $rule->user_id,
                'product_id' => $rule->product_id,
                'target_price' => $rule->target_price,
                'notify_when' => $rule->notify_when,
                'is_active' => (bool) $rule->is_active,
                'last_checked_at' => optional($rule->last_checked_at)->toDateTimeString(),
                'last_notified_at' => optional($rule->last_notified_at)->toDateTimeString(),
                'created_at' => optional($rule->created_at)->toDateTimeString(),
                'stats' => $ruleStatsByProductId[$rule->product_id] ?? [
                    'rules_count' => 0,
                    'active_count' => 0,
                    'completed_count' => 0,
                ],
                'product' => [
                    'id' => $rule->product->id,
                    'title' => $rule->product->title,
                    'symbol' => $rule->product->symbol,
                    'image_url' => $rule->product->image_url,
                    'current_price' => $rule->product->current_price,
                    'currency' => $rule->product->currency,
                ],
            ];
        }

        $rules->setCollection(new Collection($normalizedRules));

        return response()->json($rules);
    }

    /**
     * Update one tracking rule by id.
     */
    public function updateTrackingRule(Request $request, UserProduct $tracking): JsonResponse
    {
        if ((int) $tracking->user_id !== (int) $request->user()->id) {
            return response()->json(['message' => 'Tracking rule not found.'], 404);
        }

        $validated = $request->validate([
            'is_active' => ['sometimes', 'boolean'],
            'target_price' => ['nullable', 'numeric', 'min:0.00000001'],
            'notify_when' => ['sometimes', Rule::in(['below', 'above'])],
        ]);

        $product = Product::query()->find($tracking->product_id);
        if ($product) {
            $notifyWhen = 'below';
            if ($tracking->notify_when) {
                $notifyWhen = (string) $tracking->notify_when;
            }

            if (array_key_exists('notify_when', $validated)) {
                $notifyWhen = $validated['notify_when'];
            }

            $targetPrice = null;
            if (array_key_exists('target_price', $validated)) {
                if ($validated['target_price'] !== null) {
                    $targetPrice = (float) $validated['target_price'];
                }
            } elseif ($tracking->target_price !== null) {
                $targetPrice = (float) $tracking->target_price;
            }

            $directionError = $this->validateTargetDirection($product, $targetPrice, $notifyWhen);
            if ($directionError) {
                return $directionError;
            }
        }

        $updates = $validated;

        if (array_key_exists('target_price', $validated) && $validated['target_price'] === null) {
            $updates['last_notified_at'] = null;
        }

        $tracking->update($updates);
        $tracking->refresh();

        return response()->json([
            'message' => 'Tracking rule updated.',
            'tracking' => $tracking,
        ]);
    }

    /**
     * Delete one tracking rule by id.
     */
    public function destroyTrackingRule(Request $request, UserProduct $tracking): JsonResponse
    {
        if ((int) $tracking->user_id !== (int) $request->user()->id) {
            return response()->json(['message' => 'Tracking rule not found.'], 404);
        }

        $product = Product::query()->find($tracking->product_id);
        $tracking->delete();

        if ($product && $product->tracking_count > 0) {
            $product->decrement('tracking_count');
        }

        return response()->json(null, 204);
    }

    /**
     * Show asset details for any authenticated user.
     */
    public function show(Request $request, Product $product): JsonResponse
    {
        $pivot = UserProduct::where('user_id', $request->user()->id)
            ->where('product_id', $product->id)
            ->orderByDesc('created_at')
            ->first();

        return response()->json([
            'id' => $product->id,
            'title' => $product->title,
            'symbol' => $product->symbol,
            'image_url' => $product->image_url,
            'current_price' => $product->current_price,
            'price_change_24h' => $product->price_change_24h,
            'trend' => $product->trend,
            'currency' => $product->currency,
            'status' => $product->status,
            'product_page_url' => $product->product_page_url,
            'tracking' => $pivot ? [
                'id' => $pivot->id,
                'is_active' => (bool) $pivot->is_active,
                'target_price' => $pivot->target_price,
                'notify_when' => $pivot->notify_when,
                'last_checked_at' => optional($pivot->last_checked_at)->toDateTimeString(),
                'last_notified_at' => optional($pivot->last_notified_at)->toDateTimeString(),
                'created_at' => optional($pivot->created_at)->toDateTimeString(),
            ] : null,
        ]);
    }

    /**
     * Return current cached price from DB without external API calls.
     */
    public function currentPrice(Request $request, Product $product): JsonResponse
    {
        $tracking = UserProduct::where('user_id', $request->user()->id)
            ->where('product_id', $product->id)
            ->orderByDesc('created_at')
            ->first();

        if (!$tracking) {
            return response()->json(['message' => 'Asset not found in your tracking list.'], 404);
        }

        $latestHistory = PriceHistory::query()
            ->where('product_id', $product->id)
            ->orderByDesc('checked_at')
            ->first();

        $checkedAt = optional($product->last_successful_check)->toDateTimeString();
        if ($latestHistory?->checked_at) {
            $checkedAt = optional($latestHistory->checked_at)->toDateTimeString();
        }

        return response()->json([
            'product_id' => $product->id,
            'symbol' => $product->symbol,
            'price' => $product->current_price,
            'currency' => $product->currency,
            'price_change_24h' => $product->price_change_24h,
            'trend' => $product->trend,
            'checked_at' => $checkedAt,
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
            ->orderByDesc('created_at')
            ->first();

        if (!$pivot) {
            return response()->json(['message' => 'Asset not found in your tracking list.'], 404);
        }

        $notifyWhen = 'below';
        if ($pivot->notify_when) {
            $notifyWhen = (string) $pivot->notify_when;
        }

        if (array_key_exists('notify_when', $validated)) {
            $notifyWhen = $validated['notify_when'];
        }

        $targetPrice = null;
        if (array_key_exists('target_price', $validated)) {
            if ($validated['target_price'] !== null) {
                $targetPrice = (float) $validated['target_price'];
            }
        } elseif ($pivot->target_price !== null) {
            $targetPrice = (float) $pivot->target_price;
        }

        $directionError = $this->validateTargetDirection($product, $targetPrice, $notifyWhen);
        if ($directionError) {
            return $directionError;
        }

        $updates = $validated;

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

    private function validateTargetDirection(Product $product, ?float $targetPrice, string $notifyWhen): ?JsonResponse
    {
        if ($targetPrice === null || $product->current_price === null) {
            return null;
        }

        $currentPrice = (float) $product->current_price;

        if ($notifyWhen === 'below' && $targetPrice >= $currentPrice) {
            return response()->json([
                'message' => 'For "below" condition, target price must be lower than current price.',
            ], 422);
        }

        if ($notifyWhen === 'above' && $targetPrice <= $currentPrice) {
            return response()->json([
                'message' => 'For "above" condition, target price must be higher than current price.',
            ], 422);
        }

        return null;
    }
}
