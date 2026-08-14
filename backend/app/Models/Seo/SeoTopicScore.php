<?php

namespace App\Models\Seo;

use Illuminate\Database\Eloquent\Model;

class SeoTopicScore extends Model
{
    protected $table = 'seo_topic_scores';

    protected $fillable = [
        'topic', 'article_count', 'clicks_28d', 'impressions_28d', 'avg_position',
        'has_pillar', 'supporting_count', 'coverage_score', 'quality_score',
        'authority_score', 'topic_score', 'gaps', 'analyzed_at',
    ];

    protected $casts = [
        'gaps' => 'array',
        'has_pillar' => 'boolean',
        'analyzed_at' => 'datetime',
        'avg_position' => 'float',
        'topic_score' => 'float',
    ];
}
