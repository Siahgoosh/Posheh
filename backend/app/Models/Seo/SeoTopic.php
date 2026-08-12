<?php

namespace App\Models\Seo;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SeoTopic extends Model
{
    protected $table = 'seo_topics';

    protected $fillable = [
        'entity_id', 'name', 'slug', 'parent_id', 'description', 'search_intent',
        'business_value', 'coverage', 'pillar_slug', 'related_topic_ids',
        'related_location_ids', 'gaps', 'authority_score', 'status',
    ];

    protected function casts(): array
    {
        return [
            'related_topic_ids' => 'array',
            'related_location_ids' => 'array',
            'gaps' => 'array',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function entity(): BelongsTo
    {
        return $this->belongsTo(SeoEntity::class, 'entity_id');
    }
}
