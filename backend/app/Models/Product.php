<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'title',
        'symbol',
        'coingecko_id',
        'canonical_url',
        'product_page_url',
        'image_url',
        'current_price',
        'price_change_24h',
        'trend',
        'rank',
        'currency',
        'status',
        'tracking_count',
        'checks_count',
        'last_successful_check',
        'consecutive_errors',
    ];

    protected function casts(): array
    {
        return [
            'current_price' => 'decimal:8',
            'price_change_24h' => 'decimal:4',
            'last_successful_check' => 'datetime',
        ];
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_products')
            ->withPivot('check_interval', 'target_price', 'notify_when', 'last_checked_at', 'next_check_at', 'last_notified_at', 'is_active', 'created_at');
    }

    public function priceHistory(): HasMany
    {
        return $this->hasMany(PriceHistory::class)->orderByDesc('checked_at');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }
}
