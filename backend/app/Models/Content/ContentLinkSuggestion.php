<?php

namespace App\Models\Content;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContentLinkSuggestion extends Model
{
    protected $table = 'content_link_suggestions';

    protected $fillable = [
        'blog_post_id', 'target_slug', 'anchor', 'relevance', 'context_score',
        'business_value', 'destination_quality', 'total_score',
        'auto_insert_eligible', 'status',
    ];

    protected function casts(): array
    {
        return ['auto_insert_eligible' => 'boolean'];
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(\App\Models\BlogPost::class, 'blog_post_id');
    }
}
