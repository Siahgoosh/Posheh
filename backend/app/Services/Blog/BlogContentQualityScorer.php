<?php

namespace App\Services\Blog;

/**
 * Scores content usefulness for humans first, SEO second.
 * Not a keyword-density optimizer.
 */
class BlogContentQualityScorer
{
    /** @param array<string, mixed> $payload */
    public function score(array $payload): array
    {
        $title = trim((string) ($payload['title'] ?? ''));
        $content = (string) ($payload['content'] ?? '');
        $plain = $this->plainText($content);
        $wordCount = $this->wordCount($plain);
        $faq = $payload['faq'] ?? [];
        $related = $payload['related_slugs'] ?? [];
        $metaTitle = trim((string) ($payload['meta_title'] ?? ''));
        $metaDescription = trim((string) ($payload['meta_description'] ?? ''));
        $cover = trim((string) ($payload['cover_image'] ?? ''));
        $focus = trim((string) ($payload['focus_keyword'] ?? $this->firstKeyword((string) ($payload['keywords'] ?? ''))));
        $excerpt = trim((string) ($payload['excerpt'] ?? ''));
        $intent = trim((string) ($payload['search_intent'] ?? ''));
        $ctaText = trim((string) ($payload['cta_text'] ?? ''));
        $ctaUrl = trim((string) ($payload['cta_url'] ?? ''));
        $author = trim((string) ($payload['author_name'] ?? ''));

        $h2 = preg_match_all('/<h2[\s>]/i', $content) ?: 0;
        $h3 = preg_match_all('/<h3[\s>]/i', $content) ?: 0;
        $tables = preg_match_all('/<table[\s>]/i', $content) ?: 0;
        $lists = preg_match_all('/<(ul|ol)[\s>]/i', $content) ?: 0;
        $internalLinks = preg_match_all('/href=["\']\/(?:blog|register|download)[^"\']*["\']/i', $content) ?: 0;
        $images = preg_match_all('/<img\b/i', $content) ?: 0;
        $imagesWithAlt = preg_match_all('/<img\b[^>]*\balt\s*=\s*["\'][^"\']+["\']/i', $content) ?: 0;
        $roboticHits = $this->roboticPhraseHits($plain);
        $faqCount = is_array($faq) ? count(array_filter($faq, fn ($f) => ! empty($f['question']) && ! empty($f['answer']))) : 0;
        $relatedCount = is_array($related) ? count($related) : 0;

        $contentScore = $this->clamp(
            ($wordCount >= 900 ? 35 : ($wordCount >= 500 ? 22 : ($wordCount >= 250 ? 12 : 4)))
            + ($h2 >= 4 ? 15 : ($h2 >= 2 ? 10 : 3))
            + ($h3 >= 2 ? 8 : ($h3 >= 1 ? 4 : 0))
            + ($tables >= 1 || $lists >= 2 ? 12 : ($lists >= 1 ? 6 : 0))
            + ($faqCount >= 3 ? 10 : ($faqCount >= 1 ? 5 : 0))
            + (mb_strlen($excerpt) >= 80 ? 8 : 2)
            + ($roboticHits === 0 ? 12 : max(0, 12 - ($roboticHits * 3)))
        );

        $seoScore = $this->clamp(
            ($title !== '' ? 12 : 0)
            + (mb_strlen($metaTitle) >= 30 && mb_strlen($metaTitle) <= 65 ? 15 : (mb_strlen($metaTitle) > 0 ? 7 : 0))
            + (mb_strlen($metaDescription) >= 120 && mb_strlen($metaDescription) <= 165 ? 15 : (mb_strlen($metaDescription) > 0 ? 7 : 0))
            + ($focus !== '' && (mb_stripos($title, $focus) !== false || mb_stripos($plain, $focus) !== false) ? 15 : ($focus !== '' ? 5 : 0))
            + ($intent !== '' ? 8 : 0)
            + ($h2 >= 3 ? 10 : 4)
            + ($wordCount >= 600 ? 10 : 4)
            + (! $this->looksKeywordStuffed($focus, $plain) ? 15 : 3)
        );

        $readability = $this->clamp(
            ($wordCount >= 400 ? 20 : 8)
            + ($h2 >= 3 ? 20 : 8)
            + ($roboticHits === 0 ? 25 : max(5, 25 - $roboticHits * 5))
            + ($lists + $tables >= 1 ? 15 : 5)
            + (mb_strlen($plain) / max(1, $h2 + 1) < 1200 ? 20 : 10)
        );

        $internalLinking = $this->clamp(
            min(50, $internalLinks * 12)
            + min(30, $relatedCount * 8)
            + ($internalLinks >= 2 && $relatedCount >= 3 ? 20 : 0)
        );

        $imageScore = $this->clamp(
            ($cover !== '' ? 55 : 0)
            + min(25, $images * 10)
            + ($images > 0 ? (int) round(($imagesWithAlt / max(1, $images)) * 20) : ($cover !== '' ? 15 : 0))
        );

        $trust = $this->clamp(
            ($author !== '' ? 20 : 0)
            + ($faqCount >= 2 ? 15 : 0)
            + (! $this->hasUnsupportedLegalClaims($plain) ? 25 : 5)
            + (preg_match('/توصیه|مشاوره حقوقی|بازنگری|ممکن است|معمولاً/u', $plain) ? 20 : 10)
            + ($tables >= 1 ? 20 : 10)
        );

        $conversion = $this->clamp(
            ($ctaText !== '' && $ctaUrl !== '' ? 40 : 10)
            + (preg_match('/\/register|\/download|پوشه/u', $content.$ctaUrl) ? 30 : 10)
            + (($payload['business_intent'] ?? '') === 'conversion' ? 20 : 15)
            + (! preg_match('/همین حالا بخر|معجزه|۱۰۰٪ تضمین/u', $plain) ? 10 : 0)
        );

        $overall = (int) round(
            $contentScore * 0.28
            + $seoScore * 0.22
            + $readability * 0.15
            + $internalLinking * 0.12
            + $imageScore * 0.08
            + $trust * 0.08
            + $conversion * 0.07
        );

        return [
            'overall' => $this->clamp($overall),
            'grade' => $this->grade($overall),
            'content' => $contentScore,
            'seo' => $seoScore,
            'readability' => $readability,
            'internal_linking' => $internalLinking,
            'image' => $imageScore,
            'trust' => $trust,
            'conversion' => $conversion,
            'metrics' => [
                'word_count' => $wordCount,
                'h2' => $h2,
                'h3' => $h3,
                'tables' => $tables,
                'lists' => $lists,
                'internal_links' => $internalLinks,
                'faq_count' => $faqCount,
                'related_count' => $relatedCount,
                'images' => $images,
                'robotic_hits' => $roboticHits,
            ],
        ];
    }

    /** @param array<string, mixed> $before @param array<string, mixed> $after */
    public function compare(array $before, array $after): array
    {
        $b = $this->score($before);
        $a = $this->score($after);

        return [
            'before' => $b,
            'after' => $a,
            'delta' => [
                'overall' => $a['overall'] - $b['overall'],
                'content' => $a['content'] - $b['content'],
                'seo' => $a['seo'] - $b['seo'],
                'readability' => $a['readability'] - $b['readability'],
                'internal_linking' => $a['internal_linking'] - $b['internal_linking'],
                'image' => $a['image'] - $b['image'],
            ],
        ];
    }

    private function grade(int $score): string
    {
        return match (true) {
            $score >= 80 => 'A',
            $score >= 60 => 'B',
            $score >= 40 => 'C',
            default => 'D',
        };
    }

    private function clamp(float|int $n): int
    {
        return (int) max(0, min(100, round($n)));
    }

    private function plainText(string $html): string
    {
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim(preg_replace('/\s+/u', ' ', $text) ?? '');
    }

    private function wordCount(string $plain): int
    {
        if ($plain === '') {
            return 0;
        }

        return count(preg_split('/\s+/u', $plain, -1, PREG_SPLIT_NO_EMPTY) ?: []);
    }

    private function firstKeyword(string $keywords): string
    {
        $parts = array_filter(array_map('trim', preg_split('/[,،]/u', $keywords) ?: []));

        return $parts[0] ?? '';
    }

    private function roboticPhraseHits(string $plain): int
    {
        $phrases = [
            'در دنیای امروز',
            'همانطور که می‌دانید',
            'لازم به ذکر است',
            'شایان ذکر است',
            'در این مقاله قصد داریم',
            'به طور کلی',
            'می‌توان گفت که',
        ];
        $hits = 0;
        foreach ($phrases as $p) {
            if (mb_stripos($plain, $p) !== false) {
                $hits++;
            }
        }

        return $hits;
    }

    private function looksKeywordStuffed(string $focus, string $plain): bool
    {
        if ($focus === '' || $plain === '') {
            return false;
        }
        $count = mb_substr_count(mb_strtolower($plain), mb_strtolower($focus));
        $words = max(1, $this->wordCount($plain));

        return ($count / $words) * 100 > 3.5 || $count > 25;
    }

    private function hasUnsupportedLegalClaims(string $plain): bool
    {
        return (bool) preg_match('/قطعاً قانونی است|بدون نیاز به وکیل|۱۰۰٪ معتبر در دادگاه|مالیات دقیقاً \d+/u', $plain);
    }
}
