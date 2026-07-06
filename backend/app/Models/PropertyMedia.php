<?php

namespace App\Models;

use App\Enums\MediaType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PropertyMedia extends Model
{
    protected $table = 'property_media';

    protected $fillable = [
        'property_id',
        'type',
        'path',
        'original_name',
        'mime_type',
        'size',
        'sort_order',
        'is_cover',
    ];

    protected function casts(): array
    {
        return [
            'type' => MediaType::class,
            'is_cover' => 'boolean',
        ];
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }
}
