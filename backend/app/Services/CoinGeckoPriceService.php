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

        $defaults = collect($this->supportedAssets())
            ->map(fn(array $asset) => strtoupper((string) ($asset['symbol'] ?? '')))
            ->filter()
            ->values();

        if ($defaults->isEmpty()) {
            return ['synced' => 0, 'errors' => 0, 'error_details' => []];
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
            return ['synced' => 0, 'errors' => $errors, 'error_details' => $errorDetails];
        }

        $marketById = $this->fetchMarketData(array_values($coinIdBySymbol))->keyBy('id');

        foreach ($coinIdBySymbol as $symbol => $coinId) {
            $market = $marketById->get($coinId);

            if (!$market) {
                $errors++;
                $errorDetails[] = ['symbol' => $symbol, 'message' => 'Coin is missing from market response.'];
                continue;
            }

            $price = isset($market['current_price']) ? (float) $market['current_price'] : null;
            if ($price === null || $price <= 0) {
                $errors++;
                $errorDetails[] = ['symbol' => $symbol, 'message' => 'Invalid price in market response.'];
                continue;
            }

            $product = Product::firstOrNew(['coingecko_id' => $coinId]);
            $oldPrice = $product->exists && $product->current_price !== null ? (float) $product->current_price : null;

            $trend = 'flat';
            if ($oldPrice !== null) {
                if ($price > $oldPrice) {
                    $trend = 'up';
                } elseif ($price < $oldPrice) {
                    $trend = 'down';
                }
            }

            $product->fill([
                'title' => (string) ($market['name'] ?? $symbol),
                'symbol' => strtoupper((string) ($market['symbol'] ?? $symbol)),
                'canonical_url' => 'coingecko:' . $coinId,
                'product_page_url' => 'https://www.coingecko.com/en/coins/' . $coinId,
                'image_url' => $market['image'] ?? null,
                'currency' => strtoupper($this->vsCurrency),
                'status' => 'active',
                'current_price' => $price,
                'price_change_24h' => isset($market['price_change_percentage_24h'])
                    ? (float) $market['price_change_percentage_24h']
                    : null,
                'trend' => $trend,
                'checks_count' => ((int) ($product->checks_count ?? 0)) + 1,
                'last_successful_check' => now(),
                'consecutive_errors' => 0,
            ]);
            $product->save();

            $lastHistory = PriceHistory::query()
                ->where('product_id', $product->id)
                ->orderByDesc('checked_at')
                ->first();

            if (!$lastHistory || (float) $lastHistory->price !== $price) {
                PriceHistory::create([
                    'product_id' => $product->id,
                    'price' => $price,
                    'checked_at' => now(),
                ]);
            }

            $synced++;
        }

        return ['synced' => $synced, 'errors' => $errors, 'error_details' => $errorDetails];
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

        $market = $this->fetchMarketData([$coinId])->first();

        if (!$market || !isset($market['current_price'])) {
            return null;
        }

        return (float) $market['current_price'];
    }

    public function checkDuePrices(bool $force = false): array
    {
        $checked = 0;
        $errors = 0;
        $errorDetails = [];

        $query = UserProduct::query()
            ->where('is_active', true)
            ->whereHas('product', function ($q) {
                $q->where('status', 'active');
            });

        if (!$force) {
            $query->where(function ($q) {
                $q->whereNull('next_check_at')
                    ->orWhere('next_check_at', '<=', now());
            });
        }

        $dueItems = $query->with('product')->get()->unique('product_id');
        $products = $dueItems
            ->pluck('product')
            ->filter(fn($product) => $product && $product->status === 'active')
            ->keyBy('id');

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

        $marketById = collect();
        if (!empty($coinIdByProductId)) {
            try {
                $marketById = $this->fetchMarketData(array_values($coinIdByProductId))->keyBy('id');
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
                $market = $marketById->get($coinId);
                $newPrice = $market ? (float) ($market['current_price'] ?? 0) : null;
                $change24h = $market ? (float) ($market['price_change_percentage_24h'] ?? 0) : null;

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
                        'next_check_at' => now()->addMinutes($up->check_interval),
                    ]);
                });
        }

        return ['checked' => $checked, 'errors' => $errors, 'error_details' => $errorDetails];
    }

    protected function recordPrice(Product $product, float $newPrice, ?float $change24h = null): void
    {
        $oldPrice = $product->current_price !== null ? (float) $product->current_price : null;
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
                $condition = $tracker->notify_when ?? 'below';

                $triggered = ($condition === 'above' && $newPrice >= $target)
                    || ($condition === 'below' && $newPrice <= $target);

                if (!$triggered) {
                    return;
                }

                if ($tracker->last_notified_at && $tracker->last_notified_at->gt(now()->subHours(6))) {
                    return;
                }

                $symbol = strtoupper((string) ($product->symbol ?: $product->title));
                $currency = strtoupper((string) ($product->currency ?: $this->vsCurrency));

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
            $coins = collect($response->json('coins', []));

            if ($coins->isEmpty()) {
                throw new \RuntimeException("Asset {$symbol} not found in CoinGecko.");
            }

            $exact = $coins
                ->filter(fn(array $coin) => strtoupper((string) ($coin['symbol'] ?? '')) === $symbol)
                ->sortBy(fn(array $coin) => $coin['market_cap_rank'] ?? PHP_INT_MAX)
                ->first();

            $picked = $exact ?: $coins->first();
            $coinId = (string) ($picked['id'] ?? '');

            if ($coinId === '') {
                throw new \RuntimeException("Unable to resolve CoinGecko id for {$symbol}.");
            }

            return $coinId;
        });
    }

    protected function fetchMarketData(array $coinIds): Collection
    {
        $ids = array_values(array_unique(array_filter(array_map(fn($id) => trim((string) $id), $coinIds))));

        if (empty($ids)) {
            return collect();
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

        return collect($response->json());
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
            $message = $response->json('error') ?: $response->body();
            throw new \RuntimeException("CoinGecko request failed ({$status}): {$message}");
        }

        return $response;
    }
}
