<?php

namespace App\Models\Seo;

use Illuminate\Database\Eloquent\Model;

class SeoAlert extends Model
{
    protected $table = 'seo_alerts';

    protected $fillable = [
        'severity', 'kind', 'title', 'detail', 'page_url', 'is_resolved', 'resolved_at', 'payload',
    ];

    protected $casts = [
        'is_resolved' => 'boolean',
        'resolved_at' => 'datetime',
        'payload' => 'array',
    ];
}
