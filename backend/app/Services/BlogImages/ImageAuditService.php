<?php

namespace App\Services\BlogImages;

use App\Models\BlogPost;
use App\Models\Content\BlogImageAudit;
use App\Models\Seo\SeoContentHealth;
use Illuminate\Support\Facades\Schema;

/**
 * Audits blog articles for image needs. Does NOT generate images for thin/archive candidates.
 */
class ImageAuditService
{
    /** @return array{scanned:int,needs:int,skipped:int} */
    public function scanAll(int $chunk = 200): array
    {
        if (! Schema::hasTable('blog_image_audits')) {
            return ['scanned' => 0, 'needs' => 0, 'skipped' => 0, 'error' => 'Migration required'];
        }

        $scanned = 0;
        $needs = 0;
        $skipped = 0;

        BlogPost::query()->orderBy('id')->chunkById($chunk, function ($posts) use (&$scanned, &$needs, &$skipped) {
            foreach ($posts as $post) {
                $row = $this->auditPost($post);
                $scanned++;
                if ($row['should_generate']) {
                    $needs++;
                } else {
                    $skipped++;
                }
            }
        });

        return compact('scanned', 'needs', 'skipped');
    }

    /** @return array<string,mixed> */
    public function auditPost(BlogPost $post): array
    {
        $hero = trim((string) $post->cover_image);
        $hasHero = $hero !== '';
        $contentImgs = preg_match_all('/<img\b/i', (string) $post->content) ?: 0;
        $alts = preg_match_all('/<img\b[^>]*\balt\s*=\s*["\'][^"\']+["\']/i', (string) $post->content) ?: 0;
        $plain = trim(preg_replace('/\s+/u', ' ', strip_tags((string) $post->content)) ?? '');
        $words = $plain === '' ? 0 : count(preg_split('/\s+/u', $plain) ?: []);

        $status = 'IMAGE_OK';
        if (! $hasHero && $contentImgs === 0) {
            $status = 'NO_IMAGE';
        } elseif (! $hasHero) {
            $status = 'HERO_MISSING';
        } elseif ($hasHero && (str_ends_with(strtolower($hero), '.svg') || str_contains($hero, 'placeholder'))) {
            $status = 'IMAGE_WEAK';
        } elseif ($post->content_updated_at && $post->content_updated_at->lt(now()->subYear()) && $hasHero) {
            $status = 'IMAGE_NEEDS_REFRESH';
        }

        $thin = $words < 250 || ($post->review_status === BlogPost::REVIEW_ARCHIVED);
        $archiveCandidate = in_array($post->review_status, [BlogPost::REVIEW_ARCHIVED, BlogPost::REVIEW_TRASH], true);

        $traffic = 0;
        $impressions = 0;
        if (Schema::hasTable('seo_content_health') && Schema::hasColumn('seo_content_health', 'clicks_28d')) {
            $h = SeoContentHealth::query()->where('slug', $post->slug)->first();
            $traffic = (int) ($h->clicks_28d ?? 0);
            $impressions = (int) ($h->impressions_28d ?? 0);
        }

        $business = $this->businessValue($post);
        $missingBoost = in_array($status, ['NO_IMAGE', 'HERO_MISSING'], true) ? 35 : (in_array($status, ['IMAGE_WEAK', 'IMAGE_NEEDS_REFRESH'], true) ? 20 : 0);
        $contentQ = min(30, (int) round($words / 40));
        $demand = min(25, (int) round($impressions / 40));
        $score = min(100, $missingBoost + $business + $contentQ + $demand + min(15, (int) round($traffic / 5)));

        $should = false;
        $skip = null;
        $type = 'HERO';

        if ($archiveCandidate) {
            $skip = 'ARCHIVE_CANDIDATE';
        } elseif ($thin && ! $post->is_published && $traffic < 5 && $impressions < 50) {
            // Thin drafts stay skipped; published posts without covers must still get images.
            $skip = 'THIN_LOW_VALUE';
        } elseif ($status === 'IMAGE_OK' && $score < 55) {
            $skip = 'IMAGE_OK';
        } elseif (! $post->is_published && $status === 'IMAGE_OK') {
            $skip = 'DRAFT_OK';
        } else {
            $needsImage = in_array($status, ['NO_IMAGE', 'HERO_MISSING', 'IMAGE_WEAK', 'IMAGE_NEEDS_REFRESH'], true);
            // Published articles missing a hero always qualify; drafts need opportunity score.
            $should = $needsImage && ($post->is_published || $score >= 40);
            if ($should) {
                $status = $status === 'IMAGE_OK' ? 'IMAGE_GENERATION_REQUIRED' : $status;
                $type = $this->recommendType($post);
            } else {
                $skip = 'LOW_OPPORTUNITY';
            }
        }

        $priority = $score >= 80 ? 'P0' : ($score >= 65 ? 'P1' : ($score >= 45 ? 'P2' : 'P3'));

        $payload = [
            'blog_post_id' => $post->id,
            'image_status' => $should && $status === 'IMAGE_OK' ? 'IMAGE_GENERATION_REQUIRED' : $status,
            'priority' => $priority,
            'opportunity_score' => $score,
            'should_generate' => $should,
            'skip_reason' => $skip,
            'recommended_type' => $type,
            'existing_image_count' => ($hasHero ? 1 : 0) + $contentImgs,
            'has_hero' => $hasHero,
            'hero_url' => $hasHero ? $hero : null,
            'findings' => [
                'words' => $words,
                'content_images' => $contentImgs,
                'alts_with_text' => $alts,
                'clicks_28d' => $traffic ?: 'UNKNOWN',
                'impressions_28d' => $impressions ?: 'UNKNOWN',
                'note' => 'Internal Image Opportunity Score — not a Google Score',
            ],
            'audited_at' => now(),
        ];

        if (Schema::hasTable('blog_image_audits')) {
            BlogImageAudit::query()->updateOrCreate(['blog_post_id' => $post->id], $payload);
        }

        return $payload;
    }

    private function businessValue(BlogPost $post): int
    {
        $n = mb_strtolower(($post->focus_keyword ?? '').' '.($post->title ?? '').' '.($post->category_slug ?? ''));
        $score = 10;
        foreach (['crm', 'نرم‌افزار', 'پوشه', 'ثبت', 'دانلود', 'سایت', 'تور'] as $kw) {
            if (str_contains($n, $kw)) {
                $score += 8;
            }
        }
        if ($post->pillar_slug) {
            $score += 10;
        }
        if ($post->is_featured || $post->is_editors_pick) {
            $score += 10;
        }

        return min(40, $score);
    }

    private function recommendType(BlogPost $post): string
    {
        $t = mb_strtolower(($post->content_type ?? '').' '.($post->title ?? '').' '.($post->category_slug ?? ''));
        if (str_contains($t, 'local') || str_contains($t, 'محله') || str_contains($t, 'شهر')) {
            return 'LOCAL_SCENE';
        }
        if (str_contains($t, 'مقایسه') || str_contains($t, 'comparison')) {
            return 'COMPARISON';
        }
        if (str_contains($t, 'چطور') || str_contains($t, 'how') || str_contains($t, 'راهنما')) {
            return 'STEP_BY_STEP';
        }
        if (str_contains($t, 'faq')) {
            return 'FAQ_VISUAL';
        }

        return 'HERO';
    }
}
