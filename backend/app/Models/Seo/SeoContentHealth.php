<?php

namespace App\Models\Seo;

use App\Models\BlogPost;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SeoContentHealth extends Model
{
    protected $table = 'seo_content_health';

    protected $fillable = [
        'blog_post_id', 'slug', 'lifecycle', 'portfolio', 'funnel_stage', 'health',
        'quality_score', 'topic_score', 'clicks_28d', 'impressions_28d', 'ctr_28d',
        'position_28d', 'clicks_prev_28d', 'decay_pct', 'decay_reason', 'orphan_risk',
        'meta', 'analyzed_at',
    ];

    protected $casts = [
        'meta' => 'array',
        'orphan_risk' => 'boolean',
        'analyzed_at' => 'datetime',
        'ctr_28d' => 'float',
        'position_28d' => 'float',
        'decay_pct' => 'float',
        'clicks_prev_28d' => 'float',
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(BlogPost::class, 'blog_post_id');
    }
}
