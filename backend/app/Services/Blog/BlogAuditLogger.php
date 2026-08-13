<?php

namespace App\Services\Blog;

use App\Models\BlogAuditLog;
use App\Models\BlogPost;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

class BlogAuditLogger
{
    public function log(?BlogPost $post, string $action, ?array $old = null, ?array $new = null, ?Request $request = null): void
    {
        try {
            if (! Schema::hasTable('blog_audit_logs')) {
                return;
            }

            BlogAuditLog::create([
                'blog_post_id' => $post?->id,
                'user_id' => $request?->user()?->id,
                'action' => $action,
                'old_value' => $old,
                'new_value' => $new,
                'ip' => $request?->ip(),
            ]);
        } catch (Throwable $e) {
            Log::warning('blog_audit.write_failed', [
                'action' => $action,
                'post_id' => $post?->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
