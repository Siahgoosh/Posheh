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
        $add('slug', 'نامک', (bool) preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', (string) ($payload['slug'] ?? '')), true, 'نامک نامعتبر است');
        $add('category', 'دسته', trim((string) ($payload['category_slug'] ?? '')) !== '', true, 'دسته الزامی است');
        $add('author', 'نویسنده', trim((string) ($payload['author_name'] ?? '')) !== '', true, 'نویسنده مشخص نیست');
        $add('cover', 'تصویر شاخص', trim((string) ($payload['cover_image'] ?? '')) !== '', false, 'تصویر شاخص پیشنهاد می‌شود');
        $add('meta_title', 'عنوان سئو', trim((string) ($payload['meta_title'] ?? '')) !== '', true, 'عنوان سئو خالی است');
        $add('meta_description', 'توضیحات متا', trim((string) ($payload['meta_description'] ?? '')) !== '', true, 'توضیحات متا خالی است');
        $add('intent', 'نیت جستجو', trim((string) ($payload['search_intent'] ?? '')) !== '', false, 'نیت جستجو مشخص نشده');
        $add('internal_links', 'لینک داخلی', ($scores['metrics']['internal_links'] ?? 0) >= 1, false, 'حداقل یک لینک داخلی پیشنهاد می‌شود');
        $add('faq', 'پرسش‌وپاسخ', is_array($payload['faq'] ?? null) && count($payload['faq']) > 0, false, 'پرسش‌وپاسخ اختیاری ولی مفید است');
        $add('cta', 'فراخوان اقدام', trim((string) ($payload['cta_text'] ?? '')) !== '' || trim((string) ($payload['cta_url'] ?? '')) !== '', false, 'فراخوان اقدام از فاز ۶ پیشنهاد می‌شود');
        $add('canonical', 'آدرس کانونیکال', true, false, empty($payload['canonical_url']) ? 'کانونیکال پیش‌فرض (خود صفحه)' : 'کانونیکال سفارشی — با احتیاط');
        $add('quality_floor', 'کیفیت داخلی', ($scores['overall'] ?? 0) >= 55, true, 'امتیاز کیفیت داخلی برای انتشار کافی نیست (راهنمای داخلی — نمره گوگل نیست)');

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
