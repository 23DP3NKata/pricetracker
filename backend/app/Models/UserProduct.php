<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserProduct extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'product_id',
        'check_interval',
        'last_checked_at',
        'next_check_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'last_checked_at' => 'datetime',
            'next_check_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
