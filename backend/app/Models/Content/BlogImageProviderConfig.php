<?php

namespace App\Models\Content;

use Illuminate\Database\Eloquent\Model;

class BlogImageProviderConfig extends Model
{
    protected $table = 'blog_image_provider_configs';

    protected $fillable = [
        'key', 'label', 'model', 'resolution', 'quality', 'aspect_ratio',
        'timeout_sec', 'max_retries', 'cost_per_image_toman',
        'is_active', 'is_fallback', 'meta',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_fallback' => 'boolean',
            'meta' => 'array',
        ];
    }
}
