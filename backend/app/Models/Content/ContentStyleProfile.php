<?php

namespace App\Models\Content;

use Illuminate\Database\Eloquent\Model;

class ContentStyleProfile extends Model
{
    protected $table = 'content_style_profiles';

    protected $fillable = [
        'name', 'slug', 'tone', 'sentence_length', 'vocabulary', 'formality',
        'cta_style', 'persian_terminology', 'sample_slugs', 'anti_patterns',
        'is_default', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'sample_slugs' => 'array',
            'anti_patterns' => 'array',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}
