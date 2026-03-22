<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\PriceHistory;
use App\Models\Product;
use App\Models\SystemLog;
use App\Models\UserProduct;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Symfony\Component\DomCrawler\Crawler;

class PriceScraperService
{
    protected int $maxRetries = 3;
    protected int $retryDelay = 5; // seconds
    protected int $domainMinIntervalMs = 20_000;
    protected int $circuitFailThreshold = 4;
    protected int $circuitWindowSeconds = 900;
    protected int $circuitCooldownSeconds = 1800;

    protected array $userAgents = [
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:132.0) Gecko/20100101 Firefox/132.0',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.1 Safari/605.1.15',
    ];

    /**
     * Supported store configurations with CSS selectors.
     */
    protected array $stores = [
        'amazon.com' => [
            'name' => 'Amazon',
            'selectors' => [
                'title' => '#productTitle, #title',
                'price' => '.a-price-whole, .a-offscreen, #priceblock_ourprice, #priceblock_dealprice',
                'image' => '#landingImage, #imgBlkFront, .a-dynamic-image',
                'currency' => '.a-price-symbol',
            ],
            'canonicalPattern' => '/\/dp\/([A-Z0-9]{10})/',
            'canonicalUrl' => 'https://www.amazon.com/dp/{1}',
        ],
        'ebay.com' => [
            'name' => 'eBay',
            'selectors' => [
                'title' => '.x-item-title__mainTitle, h1.it-ttl',
                'price' => '.x-price-primary, #prcIsum',
                'image' => '.ux-image-carousel-item img, #icImg',
                'currency' => '.x-price-primary .ux-textspans',
            ],
            'canonicalPattern' => '/\/itm\/(\d+)/',
            'canonicalUrl' => 'https://www.ebay.com/itm/{1}',
        ],
        'rdveikals.lv' => [
            'name' => 'RD Veikals',
            'selectors' => [
                'title' => '.product-info h1',
                'price' => '.price strong',
                'image' => '.carousel_center_img img',
                'currency' => '.price',
            ],
            'canonicalPattern' => '/\/products\/[a-z]{2}\/(\d+)\/(\d+)\//i',
            'canonicalUrl' => 'https://www.rdveikals.lv/products/lv/{1}/{2}/',
            'unavailableText' => ['prece nav pieejama', 'vairs nav pieejama'],
        ],
        '1a.lv' => [
            'name' => '1a.lv',
            'selectors' => [
                'title' => '.product-righter.google-rich-snippet h1, .product-righter h1',
                'price' => '.price span, .product-price span',
                'image' => '.products-gallery-slider__slide-inner img, .product-gallery img',
                'currency' => '.price',
            ],
            'canonicalPattern' => '/\/p\/([a-z0-9-]+)\/([a-z0-9]+)/i',
            'canonicalUrl' => 'https://1a.lv/p/{1}/{2}',
        ],
        '220.lv' => [
            'name' => '220.lv',
            'selectors' => [
                'title' => '.c-product__name, h1[itemprop="name"], .product-name',
                'price' => '.c-price.h-price--x-large, .product-price, [itemprop="price"]',
                'image' => '.c-gallery-slide--image img, .c-gallery-slide--image, .product-image img',
                'currency' => '.c-price.h-price--x-large small, .c-price.h-price--x-large, meta[itemprop="priceCurrency"]',
            ],
            'canonicalPattern' => '/[?&]id=(\d+)/i',
            'canonicalUrl' => 'https://220.lv/id/{1}',
        ],
    ];

    // ─── Public API ──────────────────────────────────────────

    /**
     * Get list of supported store domains.
     */
    public function supportedStores(): array
    {
        return collect($this->stores)->map(fn($c) => $c['name'])->toArray();
    }

    /**
     * Check if a URL belongs to a supported store.
     */
    public function canHandle(string $url): bool
    {
        return $this->getStoreConfig($url) !== null;
    }

    /**
     * Normalize URL to its canonical form (removes tracking params).
     */
    public function normalizeUrl(string $url): string
    {
        $config = $this->getStoreConfig($url);
        if (!$config) {
            throw new \RuntimeException('Unsupported store.');
        }

        if (!empty($config['canonicalPattern'])) {
            if (preg_match($config['canonicalPattern'], $url, $matches)) {
                $canonical = $config['canonicalUrl'];
                foreach ($matches as $i => $val) {
                    if ($i === 0) continue;
                    $canonical = str_replace("{{$i}}", $val, $canonical);
                }
                return $canonical;
            }
        }

        // Fallback: strip tracking params
        $parsed = parse_url($url);
        if (isset($parsed['query'])) {
            parse_str($parsed['query'], $params);
            $remove = ['utm_source', 'utm_medium', 'utm_campaign', 'ref', 'tag', 'fbclid', 'gclid'];
            foreach ($remove as $p) {
                unset($params[$p]);
            }
            $parsed['query'] = http_build_query($params);
        }

        return $this->buildUrl($parsed);
    }

    /**
     * Fetch full product details from a URL (used when user adds a product).
     *
    * @return array{title: string, current_price: float, currency: string, image_url: ?string, store_name: string, canonical_url: string, product_page_url: string}
     */
    public function fetchProductDetails(string $url): array
    {
        $config = $this->getStoreConfig($url);
        if (!$config) {
            $supported = implode(', ', array_keys($this->stores));
            throw new \RuntimeException("Unsupported store. Supported: {$supported}");
        }

        $html = $this->fetchHtml($url);
        $crawler = new Crawler($html);

        // Check for unavailable product
        if (!empty($config['unavailableText'])) {
            $bodyText = mb_strtolower($crawler->filter('body')->text(''));
            foreach ($config['unavailableText'] as $phrase) {
                if (str_contains($bodyText, $phrase)) {
                    throw new \RuntimeException('Product is not available.');
                }
            }
        }

        $title = $this->extractText($crawler, $config['selectors']['title']);
        $priceText = $this->extractText($crawler, $config['selectors']['price']);
        $imageUrl = $this->extractAttribute($crawler, $config['selectors']['image'], 'src');
        $currencyText = $this->extractText($crawler, $config['selectors']['currency'] ?? '');

        if (!$title) {
            throw new \RuntimeException('Could not extract product title from page.');
        }

        $price = $this->parsePrice($priceText);
        if ($price === null) {
            throw new \RuntimeException('Could not extract price from page.');
        }

        $currency = $this->parseCurrency($priceText . ' ' . ($currencyText ?? ''));

        return [
            'title' => trim($title),
            'current_price' => $price,
            'currency' => $currency,
            'image_url' => $imageUrl ? $this->resolveImageUrl($imageUrl, $url) : null,
            'store_name' => $config['name'],
            'canonical_url' => $this->normalizeUrl($url),
            'product_page_url' => $url,
        ];
    }

    /**
     * Scrape only the current price from a product page (used for periodic checks).
     */
    public function scrapePrice(string $url): ?float
    {
        $config = $this->getStoreConfig($url);
        if (!$config) {
            return null;
        }

        $html = $this->fetchHtml($url);
        $crawler = new Crawler($html);

        $priceText = $this->extractText($crawler, $config['selectors']['price']);
        return $this->parsePrice($priceText);
    }

    /**
     * Check prices for all products that are due for a check.
     */
    public function checkDuePrices(bool $force = false): array
    {
        $query = UserProduct::where('is_active', true)
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

        $checked = 0;
        $errors = 0;
        $errorDetails = [];

        foreach ($dueItems as $userProduct) {
            $product = $userProduct->product;

            if (!$product || $product->status !== 'active') {
                continue;
            }

            $candidateUrls = $this->candidateUrlsForProduct($product);
            $scrapeUrl = $candidateUrls[0] ?? ($product->product_page_url ?: $product->url);

            try {
                $newPrice = null;
                $lastErrorMessage = null;
                $notFoundCount = 0;

                foreach ($candidateUrls as $candidateUrl) {
                    $scrapeUrl = $candidateUrl;

                    try {
                        $newPrice = $this->scrapePrice($candidateUrl);

                        if ($newPrice !== null) {
                            break;
                        }

                        $lastErrorMessage = 'Could not extract price from page.';
                    } catch (\Throwable $e) {
                        $lastErrorMessage = $e->getMessage();

                        if ($this->isNotFoundError($lastErrorMessage)) {
                            $notFoundCount++;
                            continue;
                        }

                        break;
                    }
                }

                if ($newPrice !== null) {
                    $this->recordPrice($product, $newPrice);
                    $checked++;
                } else {
                    $msg = $lastErrorMessage ?: 'Could not extract price from page.';

                    if ($notFoundCount > 0 && $notFoundCount === count($candidateUrls)) {
                        $this->markProductUnavailable($product, $msg);
                    }

                    $this->recordError($product, $msg);
                    $errorDetails[] = ['product_id' => $product->id, 'url' => $scrapeUrl, 'message' => $msg];
                    $errors++;
                }
            } catch (\Throwable $e) {
                $this->recordError($product, $e->getMessage());
                $errorDetails[] = ['product_id' => $product->id, 'url' => $scrapeUrl, 'message' => $e->getMessage()];
                $errors++;
            }

            // Update all user_products entries for this product
            UserProduct::where('product_id', $product->id)
                ->where('is_active', true)
                ->get()
                ->each(function (UserProduct $up) {
                    $up->update([
                        'last_checked_at' => now(),
                        'next_check_at' => now()->addMinutes($up->check_interval),
                    ]);
                });

            // Rate-limit: small delay between stores
            usleep(random_int(500_000, 1_500_000));
        }

        return ['checked' => $checked, 'errors' => $errors, 'error_details' => $errorDetails];
    }

    // ─── HTTP ────────────────────────────────────────────────

    /**
     * Fetch HTML from URL with retry logic and randomized headers.
     */
    protected function fetchHtml(string $url, int $attempt = 0): string
    {
        $domain = $this->extractDomain($url);

        if ($this->isDomainCircuitOpen($domain)) {
            throw new \RuntimeException("Circuit breaker active for {$domain}. Skipping request temporarily.");
        }

        $this->enforceDomainPacing($domain);

        $headers = [
            'User-Agent' => $this->userAgents[array_rand($this->userAgents)],
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
            'Accept-Language' => 'lv-LV,lv;q=0.9,en-US;q=0.8,en;q=0.7',
            'Accept-Encoding' => 'gzip, deflate',
            'Connection' => 'keep-alive',
            'Upgrade-Insecure-Requests' => '1',
            'Sec-Fetch-Dest' => 'document',
            'Sec-Fetch-Mode' => 'navigate',
            'Sec-Fetch-Site' => 'none',
            'DNT' => '1',
        ];

        // Small random delay before each request to avoid deterministic traffic patterns.
        usleep(random_int(500_000, 1_200_000));

        $response = Http::timeout(20)
            ->withHeaders($headers)
            ->get($url);

        if ($response->status() === 404) {
            throw new \RuntimeException('Product page returned 404 — product may have been removed.');
        }

        if (in_array($response->status(), [403, 429, 503]) && $attempt < $this->maxRetries) {
            $this->registerDomainFailure($domain, $response->status());

            // Exponential backoff + jitter to soften repeated retry patterns.
            $backoffSeconds = $this->retryDelay * (2 ** $attempt);
            $backoffMs = ($backoffSeconds * 1000) + random_int(300, 1500);
            usleep($backoffMs * 1000);

            return $this->fetchHtml($url, $attempt + 1);
        }

        if (!$response->successful()) {
            throw new \RuntimeException("HTTP {$response->status()} fetching {$url}");
        }

        $this->clearDomainFailures($domain);

        return $response->body();
    }

    // ─── DOM extraction ──────────────────────────────────────

    /**
     * Extract text from the first matching CSS selector.
     */
    protected function extractText(Crawler $crawler, string $selectors): ?string
    {
        foreach (explode(',', $selectors) as $selector) {
            $selector = trim($selector);
            if (!$selector) continue;
            try {
                $node = $crawler->filter($selector)->first();
                if ($node->count()) {
                    $text = trim($node->text(''));
                    if ($text !== '') return $text;
                }
            } catch (\Throwable) {
                continue;
            }
        }
        return null;
    }

    /**
     * Extract an attribute from the first matching CSS selector.
     */
    protected function extractAttribute(Crawler $crawler, string $selectors, string $attribute): ?string
    {
        foreach (explode(',', $selectors) as $selector) {
            $selector = trim($selector);
            if (!$selector) continue;
            try {
                $node = $crawler->filter($selector)->first();
                if ($node->count()) {
                    $val = trim($node->attr($attribute) ?? '');
                    if ($val !== '') return $val;
                }
            } catch (\Throwable) {
                continue;
            }
        }
        return null;
    }

    // ─── Price parsing ───────────────────────────────────────

    /**
     * Parse a numeric price from text like "€ 129,99" or "129.99 EUR".
     */
    protected function parsePrice(?string $text): ?float
    {
        if (!$text) return null;

        // Remove everything except digits, dots, commas
        $cleaned = preg_replace('/[^\d.,]/', '', $text);

        if ($cleaned === '') return null;

        // Handle European format: 1.299,99 → 1299.99 / 129,99 → 129.99
        if (preg_match('/^(\d{1,3}(?:\.\d{3})*)(?:,(\d{1,2}))?$/', $cleaned, $m)) {
            $whole = str_replace('.', '', $m[1]);
            $decimal = $m[2] ?? '00';
            $price = (float) "{$whole}.{$decimal}";
        }
        // Handle US format: 1,299.99 → 1299.99 / 129.99
        elseif (preg_match('/^(\d{1,3}(?:,\d{3})*)(?:\.(\d{1,2}))?$/', $cleaned, $m)) {
            $whole = str_replace(',', '', $m[1]);
            $decimal = $m[2] ?? '00';
            $price = (float) "{$whole}.{$decimal}";
        }
        // Simple: just digits with one separator
        elseif (preg_match('/([\d]+)[.,](\d{1,2})$/', $cleaned, $m)) {
            $price = (float) "{$m[1]}.{$m[2]}";
        } else {
            $price = (float) preg_replace('/[^0-9]/', '', $cleaned);
            if ($price > 100) $price /= 100; // assume cents
        }

        return $price > 0 ? $price : null;
    }

    /**
     * Detect currency from text.
     */
    protected function parseCurrency(string $text): string
    {
        if (str_contains($text, '€') || stripos($text, 'EUR') !== false) return 'EUR';
        if (str_contains($text, '$') || stripos($text, 'USD') !== false) return 'USD';
        if (str_contains($text, '£') || stripos($text, 'GBP') !== false) return 'GBP';
        return 'EUR';
    }

    // ─── Helpers ─────────────────────────────────────────────

    protected function resolveImageUrl(?string $imageUrl, string $baseUrl): ?string
    {
        if (!$imageUrl) return null;
        if (str_starts_with($imageUrl, 'http')) return $imageUrl;
        if (str_starts_with($imageUrl, '//')) return 'https:' . $imageUrl;

        $parsed = parse_url($baseUrl);
        $origin = ($parsed['scheme'] ?? 'https') . '://' . ($parsed['host'] ?? '');
        return $origin . '/' . ltrim($imageUrl, '/');
    }

    protected function buildUrl(array $parts): string
    {
        $url = ($parts['scheme'] ?? 'https') . '://' . ($parts['host'] ?? '');
        if (!empty($parts['port'])) $url .= ':' . $parts['port'];
        $url .= $parts['path'] ?? '/';
        if (!empty($parts['query'])) $url .= '?' . $parts['query'];
        return $url;
    }

    protected function getStoreConfig(string $url): ?array
    {
        $host = parse_url($url, PHP_URL_HOST);
        if (!$host) return null;
        $domain = preg_replace('/^www\./', '', strtolower($host));

        foreach ($this->stores as $storeDomain => $config) {
            if (str_contains($domain, $storeDomain)) {
                return array_merge(['domain' => $storeDomain], $config);
            }
        }
        return null;
    }

    protected function candidateUrlsForProduct(Product $product): array
    {
        $urls = [
            $product->product_page_url,
            $product->canonical_url,
            $product->url,
        ];

        $urls = array_filter(array_map(static fn($u) => is_string($u) ? trim($u) : '', $urls));
        return array_values(array_unique($urls));
    }

    protected function isNotFoundError(string $message): bool
    {
        $message = strtolower($message);
        return str_contains($message, '404') || str_contains($message, 'removed');
    }

    protected function markProductUnavailable(Product $product, string $reason): void
    {
        $activeTrackingUserIds = UserProduct::where('product_id', $product->id)
            ->where('is_active', true)
            ->pluck('user_id');

        if ($product->status !== 'deleted') {
            $product->update(['status' => 'deleted']);
        }

        UserProduct::where('product_id', $product->id)
            ->where('is_active', true)
            ->update([
                'is_active' => false,
                'next_check_at' => null,
            ]);

        $availabilityMessage = "Product unavailable on the site, tracking stopped. {$product->title}";

        foreach ($activeTrackingUserIds as $userId) {
            $alreadySent = Notification::where('user_id', $userId)
                ->where('product_id', $product->id)
                ->where('message', 'like', 'Product unavailable on the site, tracking stopped.%')
                ->exists();

            if ($alreadySent) {
                continue;
            }

            Notification::create([
                'user_id' => $userId,
                'product_id' => $product->id,
                'old_price' => (float) ($product->current_price ?? 0),
                'new_price' => (float) ($product->current_price ?? 0),
                'message' => $availabilityMessage,
            ]);
        }

        SystemLog::create([
            'level' => 'warning',
            'category' => 'scraper',
            'message' => "Product #{$product->id} marked deleted after repeated not-found checks. Reason: {$reason}",
            'product_id' => $product->id,
        ]);
    }

    protected function extractDomain(string $url): string
    {
        $host = parse_url($url, PHP_URL_HOST) ?: 'unknown';
        return preg_replace('/^www\./', '', strtolower($host));
    }

    protected function cacheKeyForDomain(string $domain, string $suffix): string
    {
        return "scraper:domain:{$domain}:{$suffix}";
    }

    protected function enforceDomainPacing(string $domain): void
    {
        if ($domain === 'unknown') {
            return;
        }

        $lastAtKey = $this->cacheKeyForDomain($domain, 'last_request_at_ms');
        $nowMs = (int) floor(microtime(true) * 1000);
        $lastAtMs = (int) Cache::get($lastAtKey, 0);

        if ($lastAtMs > 0) {
            $elapsed = $nowMs - $lastAtMs;
            $waitMs = max(0, $this->domainMinIntervalMs - $elapsed);

            if ($waitMs > 0) {
                // Add a small jitter so multiple domains do not align into bursts.
                usleep(($waitMs + random_int(50, 300)) * 1000);
            }
        }

        Cache::put($lastAtKey, (int) floor(microtime(true) * 1000), now()->addHours(2));
    }

    protected function registerDomainFailure(string $domain, int $statusCode): void
    {
        if ($domain === 'unknown') {
            return;
        }

        if (!in_array($statusCode, [403, 429, 503], true)) {
            return;
        }

        $failCountKey = $this->cacheKeyForDomain($domain, 'fail_count');
        $circuitUntilKey = $this->cacheKeyForDomain($domain, 'circuit_open_until');

        $failCount = (int) Cache::get($failCountKey, 0) + 1;
        Cache::put($failCountKey, $failCount, now()->addSeconds($this->circuitWindowSeconds));

        if ($failCount >= $this->circuitFailThreshold) {
            Cache::put($circuitUntilKey, now()->addSeconds($this->circuitCooldownSeconds)->timestamp, now()->addSeconds($this->circuitCooldownSeconds));
            Cache::forget($failCountKey);
        }
    }

    protected function clearDomainFailures(string $domain): void
    {
        if ($domain === 'unknown') {
            return;
        }

        Cache::forget($this->cacheKeyForDomain($domain, 'fail_count'));
    }

    protected function isDomainCircuitOpen(string $domain): bool
    {
        if ($domain === 'unknown') {
            return false;
        }

        $circuitUntilKey = $this->cacheKeyForDomain($domain, 'circuit_open_until');
        $untilTs = (int) Cache::get($circuitUntilKey, 0);

        if ($untilTs <= time()) {
            Cache::forget($circuitUntilKey);
            return false;
        }

        return true;
    }

    // ─── Price recording & notifications ─────────────────────

    protected function recordPrice(Product $product, float $newPrice): void
    {
        $oldPrice = $product->current_price;
        $priceChanged = $oldPrice === null || abs((float) $oldPrice - $newPrice) >= 0.01;

        if ($priceChanged) {
            PriceHistory::create([
                'product_id' => $product->id,
                'price' => $newPrice,
                'checked_at' => now(),
            ]);
        }

        $product->update([
            'current_price' => $newPrice,
            'last_successful_check' => now(),
            'consecutive_errors' => 0,
            'checks_count' => $product->checks_count + 1,
        ]);

        if ($oldPrice !== null && $priceChanged) {
            $direction = $newPrice < $oldPrice ? 'dropped' : 'increased';
            $diff = abs($oldPrice - $newPrice);
            $message = "{$product->title}: price {$direction} by {$product->currency} "
                . number_format($diff, 2)
                . " (from " . number_format($oldPrice, 2) . " to " . number_format($newPrice, 2) . ")";

            $trackingUserIds = UserProduct::where('product_id', $product->id)
                ->where('is_active', true)
                ->pluck('user_id');

            foreach ($trackingUserIds as $userId) {
                if (!$this->shouldCreateNotification((int) $userId, $product->id, (float) $oldPrice, $newPrice)) {
                    continue;
                }

                Notification::create([
                    'user_id' => $userId,
                    'product_id' => $product->id,
                    'old_price' => $oldPrice,
                    'new_price' => $newPrice,
                    'message' => $message,
                ]);
            }
        }
    }

    protected function shouldCreateNotification(int $userId, int $productId, float $oldPrice, float $newPrice): bool
    {
        $lastNotification = Notification::where('user_id', $userId)
            ->where('product_id', $productId)
            ->latest('created_at')
            ->first();

        if (!$lastNotification) {
            return true;
        }

        return !(
            abs((float) $lastNotification->old_price - $oldPrice) < 0.01
            && abs((float) $lastNotification->new_price - $newPrice) < 0.01
        );
    }

    protected function recordError(Product $product, string $errorMessage): void
    {
        $nextErrors = $product->consecutive_errors + 1;
        $product->update([
            'consecutive_errors' => $nextErrors,
        ]);

        if ($nextErrors >= 5) {
            SystemLog::create([
                'level' => 'warning',
                'category' => 'scraper',
                'message' => "Product #{$product->id} reached {$nextErrors} consecutive scrape errors.",
                'product_id' => $product->id,
            ]);
        }

        SystemLog::create([
            'level' => 'error',
            'category' => 'scraper',
            'message' => "Failed to scrape product #{$product->id} ({$product->url}): {$errorMessage}",
            'product_id' => $product->id,
        ]);
    }
}
