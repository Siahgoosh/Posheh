<?php

namespace App\Models\Seo;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SeoEntity extends Model
{
    protected $table = 'seo_entities';

    public const TYPES = [
        'BUSINESS', 'BRAND', 'PERSON', 'LOCATION', 'CITY', 'NEIGHBORHOOD',
        'SERVICE', 'PROPERTY', 'ARTICLE', 'CATEGORY', 'TOPIC', 'PRODUCT',
    ];

    protected $fillable = [
        'type', 'name', 'slug', 'description', 'status', 'meta_title', 'meta_description',
        'canonical_url', 'robots_directive', 'og_title', 'og_description', 'og_image',
        'schema_type', 'is_indexable', 'payload', 'external_id', 'external_type',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'is_indexable' => 'boolean',
        ];
    }

    public function outgoing(): HasMany
    {
        return $this->hasMany(SeoEntityRelationship::class, 'from_entity_id');
    }

    public function incoming(): HasMany
    {
        return $this->hasMany(SeoEntityRelationship::class, 'to_entity_id');
    }
}
