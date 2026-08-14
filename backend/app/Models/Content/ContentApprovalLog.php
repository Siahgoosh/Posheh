<?php

namespace App\Models\Content;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContentApprovalLog extends Model
{
    protected $table = 'content_approval_logs';

    protected $fillable = [
        'blog_post_id', 'user_id', 'action', 'version_id',
        'from_status', 'to_status', 'meta',
    ];

    protected function casts(): array
    {
        return ['meta' => 'array'];
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(\App\Models\BlogPost::class, 'blog_post_id');
    }
}
