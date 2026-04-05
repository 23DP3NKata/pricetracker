<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppSetting extends Model
{
    public $timestamps = true;

    protected $fillable = [
        'key',
        'value',
    ];

    public static function getBool(string $key, bool $default = false): bool
    {
        $row = static::query()->where('key', $key)->first();
        if (!$row) {
            return $default;
        }

        return filter_var($row->value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? $default;
    }

    public static function setBool(string $key, bool $value): void
    {
        static::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value ? '1' : '0']
        );
    }
}
