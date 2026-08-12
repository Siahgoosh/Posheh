<?php

namespace App\Models\Seo;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SeoLocalKnowledge extends Model
{
    protected $table = 'seo_local_knowledge';

    protected $fillable = [
        'location_id', 'topic_id', 'fact_type', 'fact', 'source', 'fact_date',
        'confidence', 'status', 'author', 'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'fact_date' => 'date',
            'expires_at' => 'datetime',
        ];
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(SeoLocation::class, 'location_id');
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(SeoTopic::class, 'topic_id');
    }
}
