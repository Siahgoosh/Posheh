<?php

namespace App\Services\Blog;

use App\Models\BlogAuditLog;
use App\Models\BlogPost;
use Illuminate\Http\Request;

class BlogAuditLogger
{
    public function log(?BlogPost $post, string $action, ?array $old = null, ?array $new = null, ?Request $request = null): void
    {
        BlogAuditLog::create([
            'blog_post_id' => $post?->id,
            'user_id' => $request?->user()?->id,
            'action' => $action,
            'old_value' => $old,
            'new_value' => $new,
            'ip' => $request?->ip(),
        ]);
    }
}
