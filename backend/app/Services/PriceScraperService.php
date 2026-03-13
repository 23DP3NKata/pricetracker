<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\PriceHistory;
use App\Models\Product;
use App\Models\SystemLog;
use App\Models\UserProduct;
use Illuminate\Support\Facades\Http;
use Symfony\Component\DomCrawler\Crawler;

class PriceScraperService
{
    protected int $maxRetries = 3;
    protected int $retryDelay = 5; // seconds

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
                'title' => 'h1[itemprop="name"], .product-name',
                'price' => '.product-price, [itemprop="price"]',
                'image' => '.product-image img',
                'currency' => 'meta[itemprop="priceCurrency"]',
            ],
            'canonicalPattern' => '/\/(\d+)/',
            'canonicalUrl' => 'https://220.lv/{1}',
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
     * @return array{title: string, current_price: float, currency: string, image_url: ?string, store_name: string, url: string}
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
            'url' => $this->normalizeUrl($url),
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
    public function checkDuePrices(): array
    {
        $dueItems = UserProduct::where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('next_check_at')
                    ->orWhere('next_check_at', '<=', now());
            })
            ->with('product')
            ->get()
            ->unique('product_id');

        $checked = 0;
        $errors = 0;

        foreach ($dueItems as $userProduct) {
            $product = $userProduct->product;

            if ($product->status !== 'active') {
                continue;
            }

            try {
                $newPrice = $this->scrapePrice($product->url);

                if ($newPrice !== null) {
                    $this->recordPrice($product, $newPrice);
                    $checked++;
                } else {
                    $this->recordError($product, 'Could not extract price from page.');
                    $errors++;
                }
            } catch (\Throwable $e) {
                $this->recordError($product, $e->getMessage());
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

        return ['checked' => $checked, 'errors' => $errors];
    }

    // ─── HTTP ────────────────────────────────────────────────

    /**
     * Fetch HTML from URL with retry logic and randomized headers.
     */
    protected function fetchHtml(string $url, int $attempt = 0): string
    {
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

        // Small random delay before each request
        usleep(random_int(1_000_000, 3_000_000));

        $response = Http::timeout(20)
            ->withHeaders($headers)
            ->get($url);

        if ($response->status() === 404) {
            throw new \RuntimeException('Product page returned 404 — product may have been removed.');
        }

        if (in_array($response->status(), [403, 429, 503]) && $attempt < $this->maxRetries) {
            sleep($this->retryDelay * ($attempt + 1));
            return $this->fetchHtml($url, $attempt + 1);
        }

        if (!$response->successful()) {
            throw new \RuntimeException("HTTP {$response->status()} fetching {$url}");
        }

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

    // ─── Price recording & notifications ─────────────────────

    protected function recordPrice(Product $product, float $newPrice): void
    {
        $oldPrice = $product->current_price;

        PriceHistory::create([
            'product_id' => $product->id,
            'price' => $newPrice,
            'checked_at' => now(),
        ]);

        $product->update([
            'current_price' => $newPrice,
            'last_successful_check' => now(),
            'consecutive_errors' => 0,
            'checks_count' => $product->checks_count + 1,
        ]);

        if ($oldPrice !== null && abs($oldPrice - $newPrice) >= 0.01) {
            $direction = $newPrice < $oldPrice ? 'dropped' : 'increased';
            $diff = abs($oldPrice - $newPrice);
            $message = "{$product->title}: price {$direction} by {$product->currency} "
                . number_format($diff, 2)
                . " (from " . number_format($oldPrice, 2) . " to " . number_format($newPrice, 2) . ")";

            $trackingUserIds = UserProduct::where('product_id', $product->id)
                ->where('is_active', true)
                ->pluck('user_id');

            foreach ($trackingUserIds as $userId) {
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

    protected function recordError(Product $product, string $errorMessage): void
    {
        $product->update([
            'consecutive_errors' => $product->consecutive_errors + 1,
        ]);

        if ($product->consecutive_errors >= 5) {
            $product->update(['status' => 'error']);
        }

        SystemLog::create([
            'level' => 'error',
            'category' => 'scraper',
            'message' => "Failed to scrape product #{$product->id} ({$product->url}): {$errorMessage}",
            'product_id' => $product->id,
        ]);
    }
}
