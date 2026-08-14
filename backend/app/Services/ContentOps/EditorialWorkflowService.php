<?php

namespace App\Services\ContentOps;

use App\Models\BlogPost;
use App\Models\Content\ContentClaim;
use App\Models\Content\ContentReviewComment;
use App\Services\Blog\BlogPublishChecklistService;
use App\Services\Blog\BlogQualityGate;
use App\Services\Blog\BlogSitemapService;
use Illuminate\Support\Facades\Schema;

class EditorialWorkflowService
{
    public function __construct(
        private readonly ContentPipelineService $pipeline,
        private readonly FactCheckEngine $facts,
        private readonly BlogQualityGate $gate,
        private readonly BlogPublishChecklistService $checklist,
        private readonly BlogSitemapService $sitemap,
    ) {}

    /** @return array<string,mixed> */
    public function approve(BlogPost $post, int $userId, array $meta = []): array
    {
        if ($this->facts->hasBlockingClaims($post->id)) {
            return [
                'ok' => false,
                'message' => 'Human fact review required for sensitive claims before approval',
            ];
        }
        $gate = $this->gate->evaluate($post->toArray(), forPublish: false);
        if (! ($gate['passed'] ?? true) && ! empty($gate['blockers'])) {
            return ['ok' => false, 'message' => 'Quality gate blockers', 'gate' => $gate];
        }

        $post = $this->pipeline->transition($post, 'APPROVED', $userId, 'approve', $meta);

        return ['ok' => true, 'post' => $post];
    }

    public function requestChanges(BlogPost $post, int $userId, string $comment, ?string $section = null): BlogPost
    {
        if (Schema::hasTable('content_review_comments')) {
            ContentReviewComment::create([
                'blog_post_id' => $post->id,
                'user_id' => $userId,
                'section' => $section,
                'body' => $comment,
                'status' => 'open',
            ]);
        }

        return $this->pipeline->transition($post, 'CHANGES_REQUESTED', $userId, 'request_changes', [
            'comment' => mb_substr($comment, 0, 500),
        ]);
    }

    public function reject(BlogPost $post, int $userId, string $reason): BlogPost
    {
        return $this->pipeline->transition($post, 'CHANGES_REQUESTED', $userId, 'reject', [
            'reason' => mb_substr($reason, 0, 500),
        ]);
    }

    /** @return array<string,mixed> */
    public function publishGate(BlogPost $post): array
    {
        $checklist = $this->checklist->evaluate($post->toArray());
        $gate = $this->gate->evaluate($post->toArray(), forPublish: true);
        $claimsBlock = $this->facts->hasBlockingClaims($post->id);

        $blockers = [];
        if ($claimsBlock) {
            $blockers[] = 'Unresolved sensitive claims';
        }
        if (! ($checklist['passed'] ?? false)) {
            $blockers[] = 'Publish checklist failed';
        }
        if (! ($gate['passed'] ?? false)) {
            $blockers[] = 'Quality gate failed';
        }
        if (! $post->title || ! preg_match('/<h1[\s>]/i', (string) $post->content) && ! $post->title) {
            // H1 may be rendered from title in SPA — require title at minimum
        }
        if (! trim((string) $post->title)) {
            $blockers[] = 'Missing title / H1';
        }

        return [
            'passed' => $blockers === [],
            'blockers' => $blockers,
            'checklist' => $checklist,
            'gate' => $gate,
            'claims_block' => $claimsBlock,
            'note' => 'AI cannot override publish gate',
        ];
    }

    public function afterPublish(BlogPost $post, ?int $userId = null): void
    {
        if (Schema::hasColumn('blog_posts', 'ops_status')) {
            $post->ops_status = 'PUBLISHED';
            $post->freshness_class = 'fresh';
            $post->save();
        }
        $this->pipeline->logApproval($post, $userId, 'publish', 'APPROVED', 'PUBLISHED', []);
        $this->sitemap->invalidate();
    }

    public function reviewClaim(ContentClaim $claim, int $userId, string $status, ?string $note = null): ContentClaim
    {
        if (! in_array($status, ['approved', 'rejected', 'needs_source'], true)) {
            throw new \InvalidArgumentException('Invalid claim status');
        }
        $claim->status = $status;
        $claim->reviewed_by = $userId;
        $claim->reviewed_at = now();
        $claim->review_note = $note;
        $claim->save();

        return $claim;
    }
}
