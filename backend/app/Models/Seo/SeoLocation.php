<?php

namespace App\Models\Seo;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SeoLocation extends Model
{
    protected $table = 'seo_locations';

    protected $fillable = [
        'entity_id', 'name', 'slug', 'type', 'parent_id', 'description', 'unique_value',
        'latitude', 'longitude', 'coords_verified', 'status', 'is_indexable', 'quality_score',
        'quality_gate', 'portfolio', 'meta_title', 'meta_description', 'robots_directive',
        'published_at', 'content_updated_at',
    ];

    protected function casts(): array
    {
        return [
            'coords_verified' => 'boolean',
            'is_indexable' => 'boolean',
            'quality_gate' => 'array',
            'latitude' => 'float',
            'longitude' => 'float',
            'published_at' => 'datetime',
            'content_updated_at' => 'datetime',
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

    public function knowledge(): HasMany
    {
        return $this->hasMany(SeoLocalKnowledge::class, 'location_id');
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published')->where('is_indexable', true);
    }
}
