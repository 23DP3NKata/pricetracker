<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'product_id',
        'old_price',
        'new_price',
        'message',
        'is_read',
    ];

    protected function casts(): array
    {
        return [
            'old_price' => 'decimal:8',
            'new_price' => 'decimal:8',
            'is_read' => 'boolean',
            'created_at' => 'datetime',
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
