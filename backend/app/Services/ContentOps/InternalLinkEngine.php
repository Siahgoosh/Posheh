<?php

namespace App\Services\ContentOps;

use App\Models\BlogPost;
use App\Models\Content\ContentAiJob;
use App\Models\Content\ContentLinkSuggestion;
use App\Services\Blog\BlogAiAssistantService;
use Illuminate\Support\Facades\Schema;

class InternalLinkEngine
{
    public function __construct(
        private readonly BlogAiAssistantService $assistant,
    ) {}

    /** @return array<string,mixed> */
    public function run(ContentAiJob $job): array
    {
        $post = BlogPost::findOrFail($job->blog_post_id);
        $assist = $this->assistant->assist('internal_links', [
            'title' => $post->title,
            'content' => $post->content,
            'focus_keyword' => $post->focus_keyword,
            'exclude_id' => $post->id,
            'category_slug' => $post->category_slug,
            'pillar_slug' => $post->pillar_slug,
        ]);

        $suggestions = $assist['result'] ?? [];
        if (! is_array($suggestions)) {
            $suggestions = [];
        }

        // Normalize list of links
        $normalized = [];
        foreach ($suggestions as $item) {
            if (is_string($item)) {
                $normalized[] = [
                    'slug' => ltrim($item, '/'),
                    'anchor' => $post->focus_keyword ?: $post->title,
                    'relevance' => 60,
                ];
            } elseif (is_array($item)) {
                $slug = (string) ($item['slug'] ?? $item['target_slug'] ?? '');
                if ($slug === '') {
                    continue;
                }
                $normalized[] = [
                    'slug' => preg_replace('#^/blog/#', '', $slug) ?: $slug,
                    'anchor' => (string) ($item['suggested_anchor'] ?? $item['anchor'] ?? $item['title'] ?? $post->focus_keyword ?: 'ادامه مطلب'),
                    'relevance' => (int) ($item['score'] ?? $item['relevance'] ?? 60),
                ];
            }
        }

        // Overlink protection + orphan detection
        $existingLinks = preg_match_all('/href=["\']\/blog\/[^"\']+["\']/i', (string) $post->content) ?: 0;
        $saved = [];
        $anchorsUsed = [];
        $destUsed = [];

        foreach ($normalized as $n) {
            $slug = $n['slug'];
            $anchor = trim($n['anchor']);
            if ($slug === '' || isset($destUsed[$slug])) {
                continue;
            }
            if (isset($anchorsUsed[mb_strtolower($anchor)])) {
                $anchor = $anchor.' — راهنما';
            }
            $dest = BlogPost::query()->where('slug', $slug)->where('is_published', true)->first();
            $destQuality = $dest ? 70 : 30;
            $business = ($dest && $dest->pillar_slug) ? 75 : 55;
            $context = min(95, 40 + (int) $n['relevance']);
            $total = (int) round(($n['relevance'] + $context + $business + $destQuality) / 4);
            $auto = $total >= 80 && $existingLinks < 6 && (bool) config('content_ops.allow_auto_insert_links', false);

            $row = [
                'blog_post_id' => $post->id,
                'target_slug' => $slug,
                'anchor' => mb_substr($anchor, 0, 120),
                'relevance' => min(100, (int) $n['relevance']),
                'context_score' => $context,
                'business_value' => $business,
                'destination_quality' => $destQuality,
                'total_score' => $total,
                'auto_insert_eligible' => $auto,
                'status' => 'suggested',
            ];
            if (Schema::hasTable('content_link_suggestions')) {
                $saved[] = ContentLinkSuggestion::query()->updateOrCreate(
                    ['blog_post_id' => $post->id, 'target_slug' => $slug],
                    $row
                );
            } else {
                $saved[] = $row;
            }
            $anchorsUsed[mb_strtolower($anchor)] = true;
            $destUsed[$slug] = true;
            if (count($saved) >= 8) {
                break;
            }
        }

        $orphan = $existingLinks === 0 && count($saved) === 0;
        if (Schema::hasColumn('blog_posts', 'orphan_flag')) {
            $post->orphan_flag = $orphan || ($existingLinks === 0 && $post->is_published);
            $post->save();
        }

        return [
            'text' => count($saved).' internal link suggestions',
            'structured' => [
                'suggestions' => $saved,
                'existing_internal_links' => $existingLinks,
                'orphan' => $orphan,
                'auto_insert' => 'Only high-confidence + natural context; disabled by default',
            ],
            'prompt_tokens' => 10,
            'completion_tokens' => 35,
            'model' => 'internal-link-local',
            'provider' => 'local',
            'confidence' => 0.72,
        ];
    }
}
