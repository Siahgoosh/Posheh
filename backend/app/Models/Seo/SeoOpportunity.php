<?php

namespace App\Models\Seo;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SeoOpportunity extends Model
{
    protected $table = 'seo_opportunities';

    protected $fillable = [
        'type', 'title', 'page_url', 'slug', 'query', 'topic', 'evidence', 'recommendation',
        'expected_benefit', 'risk', 'effort', 'impact', 'confidence', 'priority_score',
        'status', 'payload', 'detected_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'detected_at' => 'datetime',
        'priority_score' => 'float',
    ];

    public function recommendations(): HasMany
    {
        return $this->hasMany(SeoRecommendation::class, 'seo_opportunity_id');
    }
}
