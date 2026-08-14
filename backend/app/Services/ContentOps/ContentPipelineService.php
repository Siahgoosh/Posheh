<?php

namespace App\Services\ContentOps;

use App\Models\BlogPost;
use App\Models\Content\ContentApprovalLog;
use Illuminate\Support\Facades\Schema;

/**
 * Content Operating System statuses & transitions.
 * AI never auto-publishes unless admin explicitly allows + quality gate passes.
 */
class ContentPipelineService
{
    public const STATUSES = [
        'IDEA',
        'RESEARCHING',
        'BRIEF_READY',
        'DRAFTING',
        'DRAFT_READY',
        'SEO_REVIEW',
        'FACT_CHECK',
        'EDITOR_REVIEW',
        'CHANGES_REQUESTED',
        'APPROVED',
        'SCHEDULED',
        'PUBLISHED',
        'UPDATED',
        'ARCHIVED',
    ];

    /** @var array<string, list<string>> */
    public const TRANSITIONS = [
        'IDEA' => ['RESEARCHING', 'BRIEF_READY', 'ARCHIVED'],
        'RESEARCHING' => ['BRIEF_READY', 'IDEA', 'ARCHIVED'],
        'BRIEF_READY' => ['DRAFTING', 'RESEARCHING'],
        'DRAFTING' => ['DRAFT_READY', 'BRIEF_READY'],
        'DRAFT_READY' => ['SEO_REVIEW', 'FACT_CHECK', 'DRAFTING'],
        'SEO_REVIEW' => ['FACT_CHECK', 'EDITOR_REVIEW', 'CHANGES_REQUESTED'],
        'FACT_CHECK' => ['EDITOR_REVIEW', 'CHANGES_REQUESTED', 'SEO_REVIEW'],
        'EDITOR_REVIEW' => ['APPROVED', 'CHANGES_REQUESTED', 'FACT_CHECK'],
        'CHANGES_REQUESTED' => ['DRAFTING', 'DRAFT_READY', 'EDITOR_REVIEW'],
        'APPROVED' => ['SCHEDULED', 'PUBLISHED', 'CHANGES_REQUESTED'],
        'SCHEDULED' => ['PUBLISHED', 'APPROVED', 'ARCHIVED'],
        'PUBLISHED' => ['UPDATED', 'ARCHIVED'],
        'UPDATED' => ['PUBLISHED', 'ARCHIVED', 'EDITOR_REVIEW'],
        'ARCHIVED' => ['IDEA'],
    ];

    public function normalizeFromReviewStatus(?string $review, bool $published): string
    {
        if ($published) {
            return 'PUBLISHED';
        }

        return match ($review) {
            BlogPost::REVIEW_ARCHIVED, BlogPost::REVIEW_TRASH => 'ARCHIVED',
            BlogPost::REVIEW_SCHEDULED => 'SCHEDULED',
            BlogPost::REVIEW_APPROVED => 'APPROVED',
            BlogPost::REVIEW_SEO => 'SEO_REVIEW',
            BlogPost::REVIEW_CONTENT, BlogPost::REVIEW_IN_REVIEW => 'EDITOR_REVIEW',
            BlogPost::REVIEW_REJECTED => 'CHANGES_REQUESTED',
            default => 'DRAFTING',
        };
    }

    public function canTransition(string $from, string $to): bool
    {
        $from = strtoupper($from);
        $to = strtoupper($to);
        if (! in_array($to, self::STATUSES, true)) {
            return false;
        }

        return in_array($to, self::TRANSITIONS[$from] ?? [], true);
    }

    public function transition(BlogPost $post, string $to, ?int $userId = null, string $action = 'status_change', array $meta = []): BlogPost
    {
        $to = strtoupper($to);
        $from = strtoupper((string) ($post->ops_status ?: $this->normalizeFromReviewStatus($post->review_status, (bool) $post->is_published)));

        if (! $this->canTransition($from, $to) && $from !== $to) {
            throw new \InvalidArgumentException("Invalid transition {$from} → {$to}");
        }

        if ($to === 'PUBLISHED' && ! $post->auto_publish_allowed && ($meta['via'] ?? '') === 'ai_auto') {
            throw new \RuntimeException('AI auto-publish blocked — human approval required');
        }

        $post->ops_status = $to;
        if ($to === 'ARCHIVED') {
            $post->review_status = BlogPost::REVIEW_ARCHIVED;
            $post->is_published = false;
        } elseif ($to === 'APPROVED') {
            $post->review_status = BlogPost::REVIEW_APPROVED;
        } elseif ($to === 'SCHEDULED') {
            $post->review_status = BlogPost::REVIEW_SCHEDULED;
        } elseif ($to === 'PUBLISHED') {
            $post->review_status = BlogPost::REVIEW_PUBLISHED;
        } elseif ($to === 'CHANGES_REQUESTED') {
            $post->review_status = BlogPost::REVIEW_REJECTED;
        } elseif (in_array($to, ['EDITOR_REVIEW', 'FACT_CHECK', 'SEO_REVIEW'], true)) {
            $post->review_status = BlogPost::REVIEW_IN_REVIEW;
        }

        $post->save();
        $this->logApproval($post, $userId, $action, $from, $to, $meta);

        return $post->fresh();
    }

    public function logApproval(BlogPost $post, ?int $userId, string $action, ?string $from, ?string $to, array $meta = []): void
    {
        if (! Schema::hasTable('content_approval_logs')) {
            return;
        }
        ContentApprovalLog::create([
            'blog_post_id' => $post->id,
            'user_id' => $userId,
            'action' => $action,
            'from_status' => $from,
            'to_status' => $to,
            'meta' => $meta,
        ]);
    }
}
