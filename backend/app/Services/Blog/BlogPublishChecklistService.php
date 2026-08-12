<?php

namespace App\Services\Blog;

/**
 * Publish checklist — human approval still required.
 *
 * @phpstan-type Check array{id: string, label: string, ok: bool, blocking: bool, message: string}
 */
class BlogPublishChecklistService
{
    public function __construct(
        private readonly BlogQualityGate $gate,
        private readonly BlogContentQualityScorer $scorer,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     * @return array{passed: bool, blocking: list<string>, checks: list<Check>, scores: array<string, mixed>, note: string}
     */
    public function evaluate(array $payload): array
    {
        $gate = $this->gate->evaluate($payload, forPublish: true);
        $scores = $this->scorer->score($payload);
        $checks = [];

        $add = function (string $id, string $label, bool $ok, bool $blocking, string $message) use (&$checks) {
            $checks[] = compact('id', 'label', 'ok', 'blocking', 'message');
        };

        $add('title', 'عنوان', trim((string) ($payload['title'] ?? '')) !== '', true, 'عنوان الزامی است');
        $add('content', 'محتوا', mb_strlen(trim(strip_tags((string) ($payload['content'] ?? '')))) >= 300, true, 'محتوا خیلی کوتاه است');
        $add('slug', 'Slug', (bool) preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', (string) ($payload['slug'] ?? '')), true, 'Slug نامعتبر');
        $add('category', 'دسته', trim((string) ($payload['category_slug'] ?? '')) !== '', true, 'دسته الزامی است');
        $add('author', 'نویسنده', trim((string) ($payload['author_name'] ?? '')) !== '', true, 'نویسنده مشخص نیست');
        $add('cover', 'تصویر شاخص', trim((string) ($payload['cover_image'] ?? '')) !== '', false, 'تصویر شاخص پیشنهاد می‌شود');
        $add('meta_title', 'SEO Title', trim((string) ($payload['meta_title'] ?? '')) !== '', true, 'SEO Title خالی است');
        $add('meta_description', 'Meta Description', trim((string) ($payload['meta_description'] ?? '')) !== '', true, 'Meta Description خالی است');
        $add('intent', 'Search Intent', trim((string) ($payload['search_intent'] ?? '')) !== '', false, 'Intent مشخص نشده');
        $add('internal_links', 'لینک داخلی', ($scores['metrics']['internal_links'] ?? 0) >= 1, false, 'حداقل یک لینک داخلی پیشنهاد می‌شود');
        $add('faq', 'FAQ', is_array($payload['faq'] ?? null) && count($payload['faq']) > 0, false, 'FAQ اختیاری ولی مفید است');
        $add('cta', 'CTA', trim((string) ($payload['cta_text'] ?? '')) !== '' || trim((string) ($payload['cta_url'] ?? '')) !== '', false, 'CTA از فاز ۶ پیشنهاد می‌شود');
        $add('canonical', 'Canonical', true, false, empty($payload['canonical_url']) ? 'Self-canonical پیش‌فرض' : 'Canonical سفارشی — با احتیاط');
        $add('quality_floor', 'کیفیت داخلی', ($scores['overall'] ?? 0) >= 55, true, 'امتیاز کیفیت داخلی برای انتشار کافی نیست (Internal Guidance)');

        $blocking = [];
        foreach ($checks as $c) {
            if (! $c['ok'] && $c['blocking']) {
                $blocking[] = $c['message'];
            }
        }
        foreach ($gate['blockers'] as $b) {
            if (! in_array($b, $blocking, true)) {
                $blocking[] = $b;
            }
        }

        return [
            'passed' => $blocking === [],
            'blocking' => $blocking,
            'checks' => $checks,
            'scores' => $scores,
            'gate' => $gate,
            'note' => 'این چک‌لیست راهنمای داخلی است — Google Score نیست و Index شدن را تضمین نمی‌کند.',
        ];
    }
}
