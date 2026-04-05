<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    const UPDATED_AT = null;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'status',
        'status_changed_by',
        'status_changed_at',
        'monthly_limit',
        'checks_used',
        'last_username_change',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'status_changed_at' => 'datetime',
            'last_username_change' => 'datetime',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'user_products')
            ->withPivot('check_interval', 'target_price', 'notify_when', 'last_checked_at', 'next_check_at', 'last_notified_at', 'is_active', 'created_at');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }
}
