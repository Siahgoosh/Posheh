<?php

namespace App\Services\Blog;

class BlogQualityGate
{
    public function __construct(
        private readonly BlogContentQualityScorer $scorer,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     * @return array{passed: bool, blockers: list<string>, warnings: list<string>, checks: list<array{id: string, status: string, message: string}>, scores: array<string, mixed>}
     */
    public function evaluate(array $payload, bool $forPublish = false): array
    {
        $scores = $this->scorer->score($payload);
        $blockers = [];
        $warnings = [];
        $checks = [];

        $title = trim((string) ($payload['title'] ?? ''));
        $slug = trim((string) ($payload['slug'] ?? ''));
        $content = (string) ($payload['content'] ?? '');
        $plain = trim(preg_replace('/\s+/u', ' ', strip_tags($content)) ?? '');
        $metaTitle = trim((string) ($payload['meta_title'] ?? ''));
        $metaDescription = trim((string) ($payload['meta_description'] ?? ''));
        $category = trim((string) ($payload['category_slug'] ?? ''));
        $cover = trim((string) ($payload['cover_image'] ?? ''));
        $h1InContent = (bool) preg_match('/<h1[\s>]/i', $content);

        $this->push($checks, $blockers, $warnings, 'title', $title !== '', true, 'عنوان مقاله تنظیم شده است.', 'عنوان مقاله خالی است.');
        $this->push($checks, $blockers, $warnings, 'slug', $slug !== '' && (bool) preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug), true, 'Slug معتبر است.', 'Slug نامعتبر یا خالی است.');
        $this->push($checks, $blockers, $warnings, 'content', mb_strlen($plain) >= 300, true, 'محتوا حداقل طول لازم را دارد.', 'محتوا خیلی کوتاه است.');
        $this->push($checks, $blockers, $warnings, 'h1_duplicate', ! $h1InContent, false, 'H1 داخل بدنه تکراری نیست (H1 در قالب صفحه است).', 'بدنه مقاله شامل H1 است؛ ممکن است H1 تکراری شود.');
        $this->push($checks, $blockers, $warnings, 'meta_title', $metaTitle !== '', $forPublish, 'Meta title وجود دارد.', 'Meta title خالی است.');
        $this->push($checks, $blockers, $warnings, 'meta_description', $metaDescription !== '', $forPublish, 'Meta description وجود دارد.', 'Meta description خالی است.');
        $this->push($checks, $blockers, $warnings, 'category', $category !== '', $forPublish, 'دسته‌بندی مشخص است.', 'دسته‌بندی خالی است.');
        $this->push($checks, $blockers, $warnings, 'cover', $cover !== '', false, 'تصویر شاخص تنظیم شده است.', 'تصویر شاخص ندارد.');
        $this->push($checks, $blockers, $warnings, 'quality_floor', $scores['overall'] >= 55, $forPublish, 'امتیاز کیفیت قابل قبول است.', 'امتیاز کیفیت برای انتشار کافی نیست (حداقل ۵۵).');
        $this->push($checks, $blockers, $warnings, 'usefulness', $scores['content'] >= 50, false, 'محتوا از نظر ساختار مفید به نظر می‌رسد.', 'ساختار محتوا هنوز ضعیف است.');
        $this->push($checks, $blockers, $warnings, 'robotic', ($scores['metrics']['robotic_hits'] ?? 0) === 0, false, 'عبارات کلیشه‌ای رباتیک دیده نشد.', 'عبارات کلیشه‌ای در متن هست.');
        $this->push($checks, $blockers, $warnings, 'internal_links', ($scores['metrics']['internal_links'] ?? 0) >= 2, false, 'حداقل ۲ لینک داخلی در متن وجود دارد.', 'لینک داخلی کافی نیست.');

        return [
            'passed' => $blockers === [],
            'blockers' => $blockers,
            'warnings' => $warnings,
            'checks' => $checks,
            'scores' => $scores,
        ];
    }

    /**
     * @param  list<array{id: string, status: string, message: string}>  $checks
     * @param  list<string>  $blockers
     * @param  list<string>  $warnings
     */
    private function push(
        array &$checks,
        array &$blockers,
        array &$warnings,
        string $id,
        bool $ok,
        bool $blocking,
        string $passMsg,
        string $failMsg,
    ): void {
        if ($ok) {
            $checks[] = ['id' => $id, 'status' => 'pass', 'message' => $passMsg];

            return;
        }

        $checks[] = ['id' => $id, 'status' => $blocking ? 'fail' : 'warn', 'message' => $failMsg];
        if ($blocking) {
            $blockers[] = $failMsg;
        } else {
            $warnings[] = $failMsg;
        }
    }
}
