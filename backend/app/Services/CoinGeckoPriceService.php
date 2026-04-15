<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\PriceHistory;
use App\Models\Product;
use App\Models\UserProduct;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class CoinGeckoPriceService
{
    private const TRACKING_INTERVAL_MINUTES = 5;
    private const MARKETS_CACHE_TTL_SECONDS = 8;
    private const MARKETS_LOCK_SECONDS = 5;

    protected string $apiBase;
    protected string $vsCurrency;

    public function __construct()
    {
        $this->apiBase = rtrim((string) config('services.coingecko.base_url', 'https://api.coingecko.com/api/v3'), '/');
        $this->vsCurrency = strtolower((string) config('services.coingecko.vs_currency', 'usd'));
    }

    public function supportedAssets(): array
    {
        return config('services.coingecko.default_assets', [
            ['symbol' => 'BTC', 'name' => 'Bitcoin'],
            ['symbol' => 'ETH', 'name' => 'Ethereum'],
            ['symbol' => 'LTC', 'name' => 'Litecoin'],
            ['symbol' => 'SOL', 'name' => 'Solana'],
            ['symbol' => 'BNB', 'name' => 'BNB'],
            ['symbol' => 'XRP', 'name' => 'XRP'],
        ]);
    }

    public function canHandle(string $symbol): bool
    {
        try {
            $this->resolveCoinIdBySymbol($symbol);
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    public function syncDefaultTopAssets(): array
    {
        $synced = 0;
        $errors = 0;
        $errorDetails = [];
        $syncedProductIds = [];

        $defaults = [];
        foreach ($this->supportedAssets() as $asset) {
            $symbol = '';
            if (isset($asset['symbol'])) {
                $symbol = strtoupper((string) $asset['symbol']);
            }

            if ($symbol !== '') {
                $defaults[] = $symbol;
            }
        }

        if (empty($defaults)) {
            return ['synced' => 0, 'errors' => 0, 'error_details' => [], 'product_ids' => []];
        }

        $coinIdBySymbol = [];
        foreach ($defaults as $symbol) {
            try {
                $coinIdBySymbol[$symbol] = $this->resolveCoinIdBySymbol($symbol);
            } catch (\Throwable $e) {
                $errors++;
                $errorDetails[] = ['symbol' => $symbol, 'message' => $e->getMessage()];
            }
        }

        if (empty($coinIdBySymbol)) {
            return ['synced' => 0, 'errors' => $errors, 'error_details' => $errorDetails, 'product_ids' => []];
        }

        $marketById = $this->fetchMarketData(array_values($coinIdBySymbol))->keyBy('id');

        foreach ($coinIdBySymbol as $symbol => $coinId) {
            $market = $marketById->get($coinId);

            if (!$market) {
                $errors++;
                $errorDetails[] = ['symbol' => $symbol, 'message' => 'Coin is missing from market response.'];
                continue;
            }

            $price = null;
            if (isset($market['current_price'])) {
                $price = (float) $market['current_price'];
            }

            if ($price === null || $price <= 0) {
                $errors++;
                $errorDetails[] = ['symbol' => $symbol, 'message' => 'Invalid price in market response.'];
                continue;
            }

            $product = Product::firstOrNew(['coingecko_id' => $coinId]);

            $product->fill([
                'title' => (string) ($market['name'] ?? $symbol),
                'symbol' => strtoupper((string) ($market['symbol'] ?? $symbol)),
                'canonical_url' => 'coingecko:' . $coinId,
                'product_page_url' => 'https://www.coingecko.com/en/coins/' . $coinId,
                'image_url' => $market['image'] ?? null,
                'currency' => strtoupper($this->vsCurrency),
                'status' => 'active',
            ]);
            $product->save();

            $change24h = null;
            if (isset($market['price_change_percentage_24h'])) {
                $change24h = (float) $market['price_change_percentage_24h'];
            }

            $this->recordPrice(
                $product,
                $price,
                $change24h
            );

            $syncedProductIds[] = $product->id;
            $synced++;
        }

        return [
            'synced' => $synced,
            'errors' => $errors,
            'error_details' => $errorDetails,
            'product_ids' => array_values(array_unique($syncedProductIds)),
        ];
    }

    public function fetchAssetDetails(string $symbol): array
    {
        $coinId = $this->resolveCoinIdBySymbol($symbol);
        $market = $this->fetchMarketData([$coinId])->first();

        if (!$market) {
            throw new \RuntimeException('Asset is not available in CoinGecko market data.');
        }

        return [
            'title' => (string) ($market['name'] ?? strtoupper($symbol)),
            'symbol' => strtoupper((string) ($market['symbol'] ?? $symbol)),
            'coingecko_id' => (string) ($market['id'] ?? $coinId),
            'current_price' => (float) ($market['current_price'] ?? 0),
            'currency' => strtoupper($this->vsCurrency),
            'image_url' => $market['image'] ?? null,
            'canonical_url' => 'coingecko:' . (string) ($market['id'] ?? $coinId),
            'product_page_url' => 'https://www.coingecko.com/en/coins/' . (string) ($market['id'] ?? $coinId),
        ];
    }

    public function currentPrice(Product $product): ?float
    {
        $coinId = $product->coingecko_id;

        if (!$coinId) {
            if (!$product->symbol) {
                return null;
            }

            $coinId = $this->resolveCoinIdBySymbol((string) $product->symbol);
            $product->update(['coingecko_id' => $coinId]);
        }

        $priceData = $this->fetchSimplePriceData([$coinId])->get($coinId);

        if (!$priceData || !isset($priceData['current_price'])) {
            return null;
        }

        return (float) $priceData['current_price'];
    }

    public function checkDuePrices(bool $force = false, array $skipProductIds = []): array
    {
        $checked = 0;
        $errors = 0;
        $errorDetails = [];

        if ($force) {
            $productsQuery = Product::query()->where('status', 'active');

            if (!empty($skipProductIds)) {
                $productsQuery->whereNotIn('id', $skipProductIds);
            }

            $products = $productsQuery->get()->keyBy('id');
        } else {
            $query = UserProduct::query()
                ->where('is_active', true)
                ->whereHas('product', function ($q) {
                    $q->where('status', 'active');
                });

            if (!empty($skipProductIds)) {
                $query->whereNotIn('product_id', $skipProductIds);
            }

            // Scheduler runs every 5 minutes, so all active trackers are checked each cycle.
            $dueItems = $query->with('product')->get()->unique('product_id');
            $products = new Collection();
            foreach ($dueItems as $item) {
                $product = $item->product;
                if (!$product) {
                    continue;
                }

                if ($product->status !== 'active') {
                    continue;
                }

                $products->put($product->id, $product);
            }
        }

        $coinIdByProductId = [];

        foreach ($products as $product) {
            try {
                $coinId = (string) $product->coingecko_id;

                if ($coinId === '') {
                    if (!$product->symbol) {
                        throw new \RuntimeException('Asset symbol is missing.');
                    }

                    $coinId = $this->resolveCoinIdBySymbol((string) $product->symbol);
                    $product->update(['coingecko_id' => $coinId]);
                }

                $coinIdByProductId[$product->id] = $coinId;
            } catch (\Throwable $e) {
                $this->recordError($product, $e->getMessage());
                $errorDetails[] = [
                    'product_id' => $product->id,
                    'symbol' => $product->symbol,
                    'message' => $e->getMessage(),
                ];
                $errors++;
            }
        }

        $priceById = new Collection();
        if (!empty($coinIdByProductId)) {
            try {
                $priceById = $this->fetchSimplePriceData(array_values($coinIdByProductId));
            } catch (\Throwable $e) {
                return [
                    'checked' => $checked,
                    'errors' => $errors + count($coinIdByProductId),
                    'error_details' => array_merge($errorDetails, [
                        [
                            'product_id' => null,
                            'symbol' => null,
                            'message' => $e->getMessage(),
                        ],
                    ]),
                ];
            }
        }

        foreach ($products as $product) {
            $coinId = $coinIdByProductId[$product->id] ?? null;
            if (!$coinId) {
                continue;
            }

            try {
                $priceData = $priceById->get($coinId);
                $newPrice = null;
                $change24h = null;
                if ($priceData) {
                    $newPrice = (float) ($priceData['current_price'] ?? 0);
                    $change24h = (float) ($priceData['price_change_percentage_24h'] ?? 0);
                }

                if ($newPrice === null || $newPrice <= 0) {
                    throw new \RuntimeException('CoinGecko did not return a valid price.');
                }

                $this->recordPrice($product, $newPrice, $change24h);
                $checked++;
            } catch (\Throwable $e) {
                $this->recordError($product, $e->getMessage());
                $errorDetails[] = [
                    'product_id' => $product->id,
                    'symbol' => $product->symbol,
                    'message' => $e->getMessage(),
                ];
                $errors++;
            }

            UserProduct::where('product_id', $product->id)
                ->where('is_active', true)
                ->get()
                ->each(function (UserProduct $up) {
                    $up->update([
                        'last_checked_at' => now(),
                        'check_interval' => self::TRACKING_INTERVAL_MINUTES,
                        'next_check_at' => now()->addMinutes(self::TRACKING_INTERVAL_MINUTES),
                    ]);
                });
        }

        return ['checked' => $checked, 'errors' => $errors, 'error_details' => $errorDetails];
    }

    public function refreshProductNow(Product $product): array
    {
        if ($product->status !== 'active') {
            return [
                'checked' => 0,
                'errors' => 1,
                'error_details' => [[
                    'product_id' => $product->id,
                    'symbol' => $product->symbol,
                    'message' => 'Only active products can be refreshed.',
                ]],
            ];
        }

        try {
            $newPrice = $this->currentPrice($product);
            if ($newPrice === null || $newPrice <= 0) {
                throw new \RuntimeException('CoinGecko did not return a valid price.');
            }

            $coinId = (string) ($product->coingecko_id ?? '');
            $change24h = null;
            if ($coinId !== '') {
                $priceData = $this->fetchSimplePriceData([$coinId])->get($coinId);
                if ($priceData) {
                    $change24h = (float) ($priceData['price_change_percentage_24h'] ?? 0);
                }
            }

            $this->recordPrice($product, $newPrice, $change24h);

            return ['checked' => 1, 'errors' => 0, 'error_details' => []];
        } catch (\Throwable $e) {
            $this->recordError($product, $e->getMessage());

            return [
                'checked' => 0,
                'errors' => 1,
                'error_details' => [[
                    'product_id' => $product->id,
                    'symbol' => $product->symbol,
                    'message' => $e->getMessage(),
                ]],
            ];
        }
    }

    public function refreshAllActiveProducts(): array
    {
        $checked = 0;
        $errors = 0;
        $errorDetails = [];

        Product::query()
            ->where('status', 'active')
            ->chunkById(100, function ($products) use (&$checked, &$errors, &$errorDetails) {
                foreach ($products as $product) {
                    $result = $this->refreshProductNow($product);
                    $checked += (int) ($result['checked'] ?? 0);
                    $errors += (int) ($result['errors'] ?? 0);

                    if (!empty($result['error_details']) && is_array($result['error_details'])) {
                        $errorDetails = array_merge($errorDetails, $result['error_details']);
                    }
                }
            });

        return ['checked' => $checked, 'errors' => $errors, 'error_details' => $errorDetails];
    }

    protected function recordPrice(Product $product, float $newPrice, ?float $change24h = null): void
    {
        $oldPrice = null;
        if ($product->current_price !== null) {
            $oldPrice = (float) $product->current_price;
        }

        $trend = 'flat';

        if ($oldPrice !== null) {
            if ($newPrice > $oldPrice) {
                $trend = 'up';
            } elseif ($newPrice < $oldPrice) {
                $trend = 'down';
            }
        }

        $product->update([
            'current_price' => $newPrice,
            'price_change_24h' => $change24h,
            'trend' => $trend,
            'checks_count' => ((int) $product->checks_count) + 1,
            'last_successful_check' => now(),
            'consecutive_errors' => 0,
        ]);

        $lastHistory = PriceHistory::where('product_id', $product->id)
            ->orderByDesc('checked_at')
            ->first();

        if (!$lastHistory || (float) $lastHistory->price !== $newPrice) {
            PriceHistory::create([
                'product_id' => $product->id,
                'price' => $newPrice,
                'checked_at' => now(),
            ]);
        }

        if ($oldPrice !== null && $oldPrice > 0) {
            $this->dispatchTargetNotifications($product, $oldPrice, $newPrice);
        }
    }

    protected function dispatchTargetNotifications(Product $product, float $oldPrice, float $newPrice): void
    {
        UserProduct::where('product_id', $product->id)
            ->where('is_active', true)
            ->whereNotNull('target_price')
            ->get()
            ->each(function (UserProduct $tracker) use ($product, $oldPrice, $newPrice) {
                $target = (float) $tracker->target_price;
                $condition = 'below';
                if ($tracker->notify_when !== null) {
                    $condition = $tracker->notify_when;
                }

                $triggered = false;
                if ($condition === 'above' && $newPrice >= $target) {
                    $triggered = true;
                }
                if ($condition === 'below' && $newPrice <= $target) {
                    $triggered = true;
                }

                if (!$triggered) {
                    return;
                }

                if ($tracker->last_notified_at && $tracker->last_notified_at->gt(now()->subHours(6))) {
                    return;
                }

                $symbolSource = $product->title;
                if ($product->symbol) {
                    $symbolSource = $product->symbol;
                }

                $currencySource = $this->vsCurrency;
                if ($product->currency) {
                    $currencySource = $product->currency;
                }

                $symbol = strtoupper((string) $symbolSource);
                $currency = strtoupper((string) $currencySource);

                Notification::create([
                    'user_id' => $tracker->user_id,
                    'product_id' => $product->id,
                    'old_price' => $oldPrice,
                    'new_price' => $newPrice,
                    'message' => "Target price reached for {$symbol}: {$newPrice} {$currency} (target: {$target} {$currency})",
                ]);

                $tracker->update([
                    'last_notified_at' => now(),
                    'is_active' => false,
                    'next_check_at' => null,
                ]);
            });
    }

    protected function recordError(Product $product, string $message): void
    {
        $product->update([
            'consecutive_errors' => ((int) $product->consecutive_errors) + 1,
        ]);
    }

    protected function resolveCoinIdBySymbol(string $symbol): string
    {
        $symbol = strtoupper(trim($symbol));

        if ($symbol === '') {
            throw new \RuntimeException('Asset symbol is required.');
        }

        $map = config('services.coingecko.symbol_map', []);
        if (isset($map[$symbol]) && is_string($map[$symbol]) && trim($map[$symbol]) !== '') {
            return (string) $map[$symbol];
        }

        $cacheKey = 'coingecko:symbol_to_id:' . strtolower($symbol);

        return Cache::remember($cacheKey, now()->addHours(6), function () use ($symbol) {
            $response = $this->httpGet('/search', ['query' => $symbol]);
            $coins = $response->json('coins', []);
            if (!is_array($coins)) {
                $coins = [];
            }

            if (empty($coins)) {
                throw new \RuntimeException("Asset {$symbol} not found in CoinGecko.");
            }

            $exact = null;
            $bestRank = PHP_INT_MAX;

            foreach ($coins as $coin) {
                $coinSymbol = strtoupper((string) ($coin['symbol'] ?? ''));
                if ($coinSymbol !== $symbol) {
                    continue;
                }

                $rank = $coin['market_cap_rank'] ?? PHP_INT_MAX;
                if ($rank < $bestRank) {
                    $bestRank = $rank;
                    $exact = $coin;
                }
            }

            $picked = $exact;
            if (!$picked) {
                $picked = $coins[0] ?? [];
            }

            $coinId = (string) ($picked['id'] ?? '');

            if ($coinId === '') {
                throw new \RuntimeException("Unable to resolve CoinGecko id for {$symbol}.");
            }

            return $coinId;
        });
    }

    protected function fetchMarketData(array $coinIds): Collection
    {
        $ids = [];
        foreach ($coinIds as $id) {
            $value = trim((string) $id);
            if ($value !== '') {
                $ids[] = $value;
            }
        }
        $ids = array_values(array_unique($ids));

        if (empty($ids)) {
            return new Collection();
        }

        sort($ids);

        $cacheKey = 'coingecko:markets:' . $this->vsCurrency . ':' . sha1(implode(',', $ids));
        $lockKey = $cacheKey . ':lock';

        $cachedPayload = Cache::get($cacheKey);
        if (is_array($cachedPayload)) {
            return new Collection($cachedPayload);
        }

        $lock = Cache::lock($lockKey, self::MARKETS_LOCK_SECONDS);

        try {
            $cachedPayload = $lock->block(1, function () use ($cacheKey, $ids) {
                $cached = Cache::get($cacheKey);
                if (is_array($cached)) {
                    return $cached;
                }

                $response = $this->httpGet('/coins/markets', [
                    'vs_currency' => $this->vsCurrency,
                    'ids' => implode(',', $ids),
                    'order' => 'market_cap_desc',
                    'per_page' => count($ids),
                    'page' => 1,
                    'sparkline' => 'false',
                    'price_change_percentage' => '24h',
                ]);

                $payload = $response->json();
                Cache::put($cacheKey, $payload, now()->addSeconds(self::MARKETS_CACHE_TTL_SECONDS));

                return $payload;
            });
        } catch (\Throwable) {
            // If lock cannot be acquired quickly, perform direct call instead of failing the check cycle.
            $response = $this->httpGet('/coins/markets', [
                'vs_currency' => $this->vsCurrency,
                'ids' => implode(',', $ids),
                'order' => 'market_cap_desc',
                'per_page' => count($ids),
                'page' => 1,
                'sparkline' => 'false',
                'price_change_percentage' => '24h',
            ]);
            $cachedPayload = $response->json();
            Cache::put($cacheKey, $cachedPayload, now()->addSeconds(self::MARKETS_CACHE_TTL_SECONDS));
        } finally {
            try {
                $lock->release();
            } catch (\Throwable) {
                // No-op: lock may already be released by the store implementation.
            }
        }

        if (!is_array($cachedPayload)) {
            $cachedPayload = [];
        }

        return new Collection($cachedPayload);
    }

    protected function fetchSimplePriceData(array $coinIds): Collection
    {
        $ids = [];
        foreach ($coinIds as $id) {
            $value = trim((string) $id);
            if ($value !== '') {
                $ids[] = $value;
            }
        }
        $ids = array_values(array_unique($ids));

        if (empty($ids)) {
            return new Collection();
        }

        sort($ids);

        $cacheKey = 'coingecko:simple-price:' . $this->vsCurrency . ':' . sha1(implode(',', $ids));
        $lockKey = $cacheKey . ':lock';

        $cachedPayload = Cache::get($cacheKey);
        if (is_array($cachedPayload)) {
            return new Collection($cachedPayload);
        }

        $lock = Cache::lock($lockKey, self::MARKETS_LOCK_SECONDS);

        try {
            $cachedPayload = $lock->block(1, function () use ($cacheKey, $ids) {
                $cached = Cache::get($cacheKey);
                if (is_array($cached)) {
                    return $cached;
                }

                $response = $this->httpGet('/simple/price', [
                    'ids' => implode(',', $ids),
                    'vs_currencies' => $this->vsCurrency,
                    'include_24hr_change' => 'true',
                ]);

                $payload = $this->normalizeSimplePricePayload($ids, $response->json());
                Cache::put($cacheKey, $payload, now()->addSeconds(self::MARKETS_CACHE_TTL_SECONDS));

                return $payload;
            });
        } catch (\Throwable) {
            // If lock cannot be acquired quickly, perform direct call instead of failing the check cycle.
            $response = $this->httpGet('/simple/price', [
                'ids' => implode(',', $ids),
                'vs_currencies' => $this->vsCurrency,
                'include_24hr_change' => 'true',
            ]);
            $cachedPayload = $this->normalizeSimplePricePayload($ids, $response->json());
            Cache::put($cacheKey, $cachedPayload, now()->addSeconds(self::MARKETS_CACHE_TTL_SECONDS));
        } finally {
            try {
                $lock->release();
            } catch (\Throwable) {
                // No-op: lock may already be released by the store implementation.
            }
        }

        if (!is_array($cachedPayload)) {
            $cachedPayload = [];
        }

        return new Collection($cachedPayload);
    }

    protected function normalizeSimplePricePayload(array $ids, mixed $raw): array
    {
        $source = [];
        if (is_array($raw)) {
            $source = $raw;
        }

        $payload = [];

        foreach ($ids as $id) {
            $entry = [];
            if (is_array($source[$id] ?? null)) {
                $entry = $source[$id];
            }

            $currentPrice = null;
            if (isset($entry[$this->vsCurrency])) {
                $currentPrice = (float) $entry[$this->vsCurrency];
            }

            $change24h = null;
            if (isset($entry[$this->vsCurrency . '_24h_change'])) {
                $change24h = (float) $entry[$this->vsCurrency . '_24h_change'];
            }

            $payload[$id] = [
                'id' => $id,
                'current_price' => $currentPrice,
                'price_change_percentage_24h' => $change24h,
            ];
        }

        return $payload;
    }

    protected function httpGet(string $path, array $query = []): Response
    {
        $request = Http::baseUrl($this->apiBase)
            ->acceptJson()
            ->timeout((int) config('services.coingecko.timeout', 15))
            ->withHeaders([
                'User-Agent' => 'PriceTracker/1.0',
            ]);

        $apiKey = trim((string) config('services.coingecko.api_key', ''));
        if ($apiKey !== '') {
            $request = $request->withHeaders([
                'x-cg-demo-api-key' => $apiKey,
            ]);
        }

        $response = $request->get($path, $query);

        if (!$response->successful()) {
            $status = $response->status();
            $message = $response->json('error');
            if (!$message) {
                $message = $response->body();
            }

            throw new \RuntimeException("CoinGecko request failed ({$status}): {$message}");
        }

        return $response;
    }
}
