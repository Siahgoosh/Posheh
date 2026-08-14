<?php

namespace App\Models\Seo;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SeoEntityRelationship extends Model
{
    protected $table = 'seo_entity_relationships';

    public const TYPES = [
        'OWNS', 'OPERATES', 'SERVES', 'LOCATED_IN', 'PART_OF', 'RELATED_TO',
        'AUTHORED_BY', 'ABOUT', 'OFFERS', 'HAS_PROPERTY', 'HAS_ARTICLE', 'NEAR', 'WORKS_FOR',
    ];

    protected $fillable = [
        'from_entity_id', 'to_entity_id', 'relation_type', 'weight', 'meta',
    ];

    protected function casts(): array
    {
        return ['meta' => 'array'];
    }

    public function from(): BelongsTo
    {
        return $this->belongsTo(SeoEntity::class, 'from_entity_id');
    }

    public function to(): BelongsTo
    {
        return $this->belongsTo(SeoEntity::class, 'to_entity_id');
    }
}
