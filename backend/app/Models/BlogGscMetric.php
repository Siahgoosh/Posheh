<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BlogGscMetric extends Model
{
    protected $table = 'blog_gsc_metrics';

    protected $fillable = [
        'page_url', 'query', 'query_raw', 'query_normalized', 'clicks', 'impressions',
        'ctr', 'position', 'device', 'country', 'search_appearance', 'date', 'synced_at',
    ];

    protected $casts = [
        'date' => 'date',
        'synced_at' => 'datetime',
        'ctr' => 'float',
        'position' => 'float',
    ];
}
