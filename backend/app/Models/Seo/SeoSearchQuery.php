<?php

namespace App\Models\Seo;

use Illuminate\Database\Eloquent\Model;

class SeoSearchQuery extends Model
{
    protected $table = 'seo_search_queries';

    protected $fillable = [
        'query_raw', 'query_normalized', 'intent', 'topic', 'entity', 'funnel_stage',
        'business_value', 'current_page_url', 'potential_page_url', 'cluster_key',
        'clicks_28d', 'impressions_28d', 'ctr_28d', 'position_28d', 'last_seen_at',
    ];

    protected $casts = [
        'last_seen_at' => 'datetime',
        'ctr_28d' => 'float',
        'position_28d' => 'float',
    ];
}
