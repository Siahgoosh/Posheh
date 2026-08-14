<?php

namespace App\Models\Content;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContentRepurposeAsset extends Model
{
    protected $table = 'content_repurpose_assets';

    protected $fillable = [
        'blog_post_id', 'job_id', 'channel', 'content', 'status', 'quality_notes',
    ];

    protected function casts(): array
    {
        return ['quality_notes' => 'array'];
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(\App\Models\BlogPost::class, 'blog_post_id');
    }
}
