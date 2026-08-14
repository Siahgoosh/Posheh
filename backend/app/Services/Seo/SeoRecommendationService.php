<?php

namespace App\Services\Seo;

use App\Models\BlogPost;
use App\Models\Seo\SeoRecommendation;
use App\Models\User;
use App\Services\Blog\BlogVersioningService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SeoRecommendationService
{
    public function __construct(
        private readonly BlogVersioningService $versions,
    ) {}

    public function approve(SeoRecommendation $rec, User $user): SeoRecommendation
    {
        if (! in_array($rec->status, ['NEW', 'REVIEWED'], true)) {
            throw ValidationException::withMessages(['status' => ['فقط پیشنهادهای NEW/REVIEWED قابل تأیید هستند.']]);
        }
        $rec->update([
            'status' => 'APPROVED',
            'approved_by' => $user->id,
            'approved_at' => now(),
        ]);

        return $rec->fresh();
    }

    public function reject(SeoRecommendation $rec, User $user, ?string $notes = null): SeoRecommendation
    {
        $rec->update([
            'status' => 'REJECTED',
            'approved_by' => $user->id,
            'approved_at' => now(),
            'result_notes' => $notes,
        ]);

        return $rec->fresh();
    }

    /**
     * Executes only low-risk approved actions that do not auto-rewrite content.
     * Major rewrite/merge/redirect stay MANUAL (status stays APPROVED until human CMS edit).
     */
    public function execute(SeoRecommendation $rec, User $user): SeoRecommendation
    {
        if ($rec->status !== 'APPROVED') {
            throw ValidationException::withMessages(['status' => ['ابتدا پیشنهاد باید APPROVED شود.']]);
        }

        if (in_array($rec->action_type, ['merge', 'rewrite', 'redirect', 'noindex'], true)
            || $rec->automation_level === 'MANUAL') {
            $rec->update([
                'result_notes' => 'نیاز به اجرای دستی در CMS — خودکار اجرا نشد (ایمنی Phase 5).',
            ]);

            return $rec->fresh();
        }

        return DB::transaction(function () use ($rec) {
            $payload = $rec->payload ?? [];
            $slug = $payload['from'] ?? $payload['slug'] ?? null;
            if (! $slug && $rec->opportunity) {
                $slug = $rec->opportunity->slug;
            }
            $post = $slug ? BlogPost::query()->where('slug', $slug)->first() : null;

            $before = $post ? [
                'meta_title' => $post->meta_title,
                'title' => $post->title,
                'related_slugs' => $post->related_slugs,
            ] : null;

            if ($post) {
                $this->versions->snapshot($post, 'seo-recommendation-'.$rec->id, 'seo-engine');
            }

            $after = $before;
            $notes = 'Executed assisted action: '.$rec->action_type;

            if ($rec->action_type === 'internal_link' && $post && ! empty($payload['to'])) {
                $related = is_array($post->related_slugs) ? $post->related_slugs : [];
                if (! in_array($payload['to'], $related, true)) {
                    $related[] = $payload['to'];
                    $post->related_slugs = array_values(array_unique($related));
                    $post->save();
                }
                $after = ['related_slugs' => $post->related_slugs];
                $notes = 'related_slugs updated with '.$payload['to'];
            }

            // title_opt / expand / faq / refresh: mark ready for editor, do not auto-rewrite STAR content
            if (in_array($rec->action_type, ['title_opt', 'meta_opt', 'expand', 'faq', 'refresh'], true) && $post) {
                $notes = 'Snapshot گرفته شد. تغییر Title/محتوا باید در ادیتور با تأیید انسانی اعمال شود (Refresh Protection).';
            }

            $rec->update([
                'status' => 'EXECUTED',
                'executed_at' => now(),
                'before_snapshot' => $before,
                'after_snapshot' => $after,
                'result_notes' => $notes,
            ]);

            return $rec->fresh();
        });
    }

    public function rollback(SeoRecommendation $rec): SeoRecommendation
    {
        if ($rec->status !== 'EXECUTED') {
            throw ValidationException::withMessages(['status' => ['فقط EXECUTED قابل Rollback است.']]);
        }
        $before = $rec->before_snapshot ?? [];
        $payload = $rec->payload ?? [];
        $slug = $payload['from'] ?? $payload['slug'] ?? $rec->opportunity?->slug;
        $post = $slug ? BlogPost::query()->where('slug', $slug)->first() : null;

        if ($post && isset($before['related_slugs'])) {
            $post->related_slugs = $before['related_slugs'];
            $post->save();
        }

        $rec->update([
            'status' => 'ROLLED_BACK',
            'rolled_back_at' => now(),
            'result_notes' => trim(($rec->result_notes ?? '').' | rolled back'),
        ]);

        return $rec->fresh();
    }
}
