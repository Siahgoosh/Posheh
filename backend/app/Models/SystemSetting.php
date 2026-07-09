<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SystemSetting extends Model
{
    protected $fillable = [
        'group',
        'key',
        'value',
        'label',
        'type',
        'is_secret',
    ];

    protected function casts(): array
    {
        return [
            'is_secret' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        $forget = static function (): void {
            try {
                Cache::forget('system_settings');
            } catch (\Throwable) {
                // Allow seeding/setup when cache backend is misconfigured.
            }
        };

        static::saved($forget);
        static::deleted($forget);
    }
}
