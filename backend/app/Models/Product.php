<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'title',
        'url',
        'image_url',
        'current_price',
        'currency',
        'store_name',
        'status',
        'tracking_count',
        'checks_count',
        'last_successful_check',
        'consecutive_errors',
    ];

    protected function casts(): array
    {
        return [
            'current_price' => 'decimal:2',
            'last_successful_check' => 'datetime',
        ];
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_products')
            ->withPivot('check_interval', 'last_checked_at', 'next_check_at', 'is_active', 'created_at');
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
