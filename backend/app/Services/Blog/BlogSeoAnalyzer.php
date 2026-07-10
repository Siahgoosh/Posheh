<?php

namespace App\Services\Blog;

class BlogSeoAnalyzer
{
    /** @param array<string, mixed> $payload */
    public function analyze(array $payload): array
    {
        $title = trim((string) ($payload['title'] ?? ''));
        $metaTitle = trim((string) ($payload['meta_title'] ?? ''));
        $metaDescription = trim((string) ($payload['meta_description'] ?? ''));
        $excerpt = trim((string) ($payload['excerpt'] ?? ''));
        $content = (string) ($payload['content'] ?? '');
        $slug = trim((string) ($payload['slug'] ?? ''));
        $keywords = trim((string) ($payload['keywords'] ?? ''));
        $coverImage = trim((string) ($payload['cover_image'] ?? ''));

        $plainContent = $this->plainText($content);
        $focusKeyword = $this->firstKeyword($keywords);
        $effectiveMetaTitle = $metaTitle !== '' ? $metaTitle : $title;

        $checks = [
            $this->checkTitleLength($title),
            $this->checkMetaTitle($effectiveMetaTitle),
            $this->checkMetaDescription($metaDescription, $excerpt),
            $this->checkSlug($slug, $focusKeyword),
            $this->checkFocusKeyword($focusKeyword, $title, $plainContent),
            $this->checkContentLength($plainContent),
            $this->checkExcerpt($excerpt),
            $this->checkCoverImage($coverImage),
            $this->checkHeadings($content),
            $this->checkLinks($content),
            $this->checkImagesAlt($content),
            $this->checkKeywordDensity($focusKeyword, $plainContent),
        ];

        $score = (int) round(array_sum(array_column($checks, 'score')));

        return [
            'score' => min(100, max(0, $score)),
            'grade' => $this->grade($score),
            'checks' => $checks,
            'summary' => $this->summary($score),
        ];
    }

    /** @return array{id: string, label: string, score: float, max: float, status: string, message: string} */
    private function checkTitleLength(string $title): array
    {
        $len = mb_strlen($title);
        $max = 15.0;

        if ($len === 0) {
            return $this->result('title', 'عنوان مقاله', 0, $max, 'fail', 'عنوان الزامی است.');
        }

        if ($len >= 30 && $len <= 60) {
            return $this->result('title', 'طول عنوان', $max, $max, 'pass', "عنوان {$len} کاراکتر — مناسب گوگل.");
        }

        if ($len < 30) {
            return $this->result('title', 'طول عنوان', 8, $max, 'warn', "عنوان کوتاه است ({$len} کاراکتر). پیشنهاد: ۳۰–۶۰ کاراکتر.");
        }

        return $this->result('title', 'طول عنوان', 6, $max, 'warn', "عنوان بلند است ({$len} کاراکتر). پیشنهاد: حداکثر ۶۰ کاراکتر.");
    }

    /** @return array{id: string, label: string, score: float, max: float, status: string, message: string} */
    private function checkMetaTitle(string $metaTitle): array
    {
        $len = mb_strlen($metaTitle);
        $max = 10.0;

        if ($len === 0) {
            return $this->result('meta_title', 'عنوان سئو (Title Tag)', 0, $max, 'fail', 'meta title خالی است.');
        }

        if ($len >= 50 && $len <= 60) {
            return $this->result('meta_title', 'عنوان سئو (Title Tag)', $max, $max, 'pass', "طول meta title مناسب ({$len}).");
        }

        if ($len < 50) {
            return $this->result('meta_title', 'عنوان سئو (Title Tag)', 6, $max, 'warn', "meta title کوتاه ({$len}). پیشنهاد: ۵۰–۶۰ کاراکتر.");
        }

        return $this->result('meta_title', 'عنوان سئو (Title Tag)', 5, $max, 'warn', "meta title بلند ({$len}). ممکن است در گوگل بریده شود.");
    }

    /** @return array{id: string, label: string, score: float, max: float, status: string, message: string} */
    private function checkMetaDescription(string $metaDescription, string $excerpt): array
    {
        $text = $metaDescription !== '' ? $metaDescription : $excerpt;
        $len = mb_strlen($text);
        $max = 15.0;

        if ($len === 0) {
            return $this->result('meta_description', 'توضیحات متا (Meta Description)', 0, $max, 'fail', 'meta description خالی است.');
        }

        if ($len >= 120 && $len <= 160) {
            return $this->result('meta_description', 'توضیحات متا (Meta Description)', $max, $max, 'pass', "طول توضیحات متا عالی ({$len}).");
        }

        if ($len < 120) {
            return $this->result('meta_description', 'توضیحات متا (Meta Description)', 8, $max, 'warn', "توضیحات متا کوتاه ({$len}). پیشنهاد: ۱۲۰–۱۶۰ کاراکتر.");
        }

        return $this->result('meta_description', 'توضیحات متا (Meta Description)', 7, $max, 'warn', "توضیحات متا بلند ({$len}).");
    }

    /** @return array{id: string, label: string, score: float, max: float, status: string, message: string} */
    private function checkSlug(string $slug, string $focusKeyword): array
    {
        $max = 10.0;

        if ($slug === '') {
            return $this->result('slug', 'نامک (Slug) URL', 0, $max, 'fail', 'slug الزامی است.');
        }

        if (! preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
            return $this->result('slug', 'نامک (Slug) URL', 4, $max, 'warn', 'slug باید انگلیسی، کوچک و با خط تیره باشد.');
        }

        if (mb_strlen($slug) > 75) {
            return $this->result('slug', 'نامک (Slug) URL', 5, $max, 'warn', 'slug خیلی بلند است.');
        }

        if ($focusKeyword !== '' && ! str_contains($slug, str_replace(' ', '-', $this->latinKeyword($focusKeyword)))) {
            return $this->result('slug', 'نامک (Slug) URL', 7, $max, 'warn', 'کلمه کلیدی اصلی در slug دیده نمی‌شود.');
        }

        return $this->result('slug', 'نامک (Slug) URL', $max, $max, 'pass', 'slug مناسب برای URL است.');
    }

    /** @return array{id: string, label: string, score: float, max: float, status: string, message: string} */
    private function checkFocusKeyword(string $keyword, string $title, string $plainContent): array
    {
        $max = 10.0;

        if ($keyword === '') {
            return $this->result('keyword', 'کلمه کلیدی اصلی', 0, $max, 'fail', 'حداقل یک کلمه کلیدی وارد کنید.');
        }

        $inTitle = mb_stripos($title, $keyword) !== false;
        $inContent = mb_stripos($plainContent, $keyword) !== false;

        if ($inTitle && $inContent) {
            return $this->result('keyword', 'کلمه کلیدی اصلی', $max, $max, 'pass', 'کلمه کلیدی در عنوان و متن وجود دارد.');
        }

        if ($inTitle || $inContent) {
            return $this->result('keyword', 'کلمه کلیدی اصلی', 6, $max, 'warn', 'کلمه کلیدی باید هم در عنوان و هم در متن باشد.');
        }

        return $this->result('keyword', 'کلمه کلیدی اصلی', 2, $max, 'fail', 'کلمه کلیدی در عنوان یا متن یافت نشد.');
    }

    /** @return array{id: string, label: string, score: float, max: float, status: string, message: string} */
    private function checkContentLength(string $plainContent): array
    {
        $len = mb_strlen($plainContent);
        $max = 15.0;

        if ($len < 300) {
            return $this->result('content_length', 'طول محتوا', 3, $max, 'fail', "متن خیلی کوتاه ({$len} کاراکتر). حداقل ۳۰۰ پیشنهاد می‌شود.");
        }

        if ($len >= 600) {
            return $this->result('content_length', 'طول محتوا', $max, $max, 'pass', "طول محتوا مناسب ({$len} کاراکتر).");
        }

        return $this->result('content_length', 'طول محتوا', 10, $max, 'warn', "متن قابل قبول ({$len}). برای رتبه بهتر ۶۰۰+ کاراکتر بنویسید.");
    }

    /** @return array{id: string, label: string, score: float, max: float, status: string, message: string} */
    private function checkExcerpt(string $excerpt): array
    {
        $len = mb_strlen($excerpt);
        $max = 5.0;

        if ($len === 0) {
            return $this->result('excerpt', 'خلاصه مقاله', 0, $max, 'fail', 'خلاصه مقاله خالی است.');
        }

        if ($len >= 80 && $len <= 200) {
            return $this->result('excerpt', 'خلاصه مقاله', $max, $max, 'pass', 'خلاصه مناسب است.');
        }

        return $this->result('excerpt', 'خلاصه مقاله', 3, $max, 'warn', 'خلاصه را بین ۸۰ تا ۲۰۰ کاراکتر بنویسید.');
    }

    /** @return array{id: string, label: string, score: float, max: float, status: string, message: string} */
    private function checkCoverImage(string $coverImage): array
    {
        $max = 10.0;

        if ($coverImage === '') {
            return $this->result('cover_image', 'تصویر شاخص', 0, $max, 'warn', 'تصویر شاخص برای سئو و شبکه‌های اجتماعی توصیه می‌شود.');
        }

        return $this->result('cover_image', 'تصویر شاخص', $max, $max, 'pass', 'تصویر شاخص تنظیم شده است.');
    }

    /** @return array{id: string, label: string, score: float, max: float, status: string, message: string} */
    private function checkHeadings(string $content): array
    {
        $max = 5.0;
        $hasH2 = (bool) preg_match('/<h2[\s>]/i', $content);

        if ($hasH2) {
            return $this->result('headings', 'ساختار عنوان‌ها (H2)', $max, $max, 'pass', 'حداقل یک H2 در متن وجود دارد.');
        }

        return $this->result('headings', 'ساختار عنوان‌ها (H2)', 1, $max, 'warn', 'برای سئو بهتر از تیتر H2 در متن استفاده کنید.');
    }

    /** @return array{id: string, label: string, score: float, max: float, status: string, message: string} */
    private function checkLinks(string $content): array
    {
        $max = 5.0;
        $hasLink = (bool) preg_match('/<a\s+[^>]*href=/i', $content);

        if ($hasLink) {
            return $this->result('links', 'لینک‌دهی داخلی/خارجی', $max, $max, 'pass', 'متن شامل لینک است.');
        }

        return $this->result('links', 'لینک‌دهی داخلی/خارجی', 1, $max, 'warn', 'افزودن لینک به منابع یا صفحات داخلی توصیه می‌شود.');
    }

    /** @return array{id: string, label: string, score: float, max: float, status: string, message: string} */
    private function checkImagesAlt(string $content): array
    {
        $max = 5.0;

        if (! preg_match_all('/<img\b[^>]*>/i', $content, $matches)) {
            return $this->result('image_alt', 'متن جایگزین تصاویر (alt)', $max, $max, 'pass', 'تصویری در متن نیست یا alt لازم نیست.');
        }

        $missingAlt = 0;
        foreach ($matches[0] as $tag) {
            if (! preg_match('/\balt\s*=\s*["\'][^"\']+["\']/i', $tag)) {
                $missingAlt++;
            }
        }

        if ($missingAlt === 0) {
            return $this->result('image_alt', 'متن جایگزین تصاویر (alt)', $max, $max, 'pass', 'همه تصاویر alt دارند.');
        }

        return $this->result('image_alt', 'متن جایگزین تصاویر (alt)', 1, $max, 'warn', "{$missingAlt} تصویر بدون alt یافت شد.");
    }

    /** @return array{id: string, label: string, score: float, max: float, status: string, message: string} */
    private function checkKeywordDensity(string $keyword, string $plainContent): array
    {
        $max = 5.0;

        if ($keyword === '' || $plainContent === '') {
            return $this->result('density', 'چگالی کلمه کلیدی', 0, $max, 'warn', 'برای تحلیل چگالی، کلمه کلیدی و متن لازم است.');
        }

        $words = max(1, preg_match_all('/\S+/u', $plainContent));
        $count = mb_substr_count(mb_strtolower($plainContent), mb_strtolower($keyword));
        $density = ($count / $words) * 100;

        if ($density >= 0.5 && $density <= 2.5) {
            return $this->result('density', 'چگالی کلمه کلیدی', $max, $max, 'pass', sprintf('چگالی مناسب (%.1f%%).', $density));
        }

        if ($density < 0.5) {
            return $this->result('density', 'چگالی کلمه کلیدی', 2, $max, 'warn', sprintf('چگالی کم (%.1f%%). کلمه کلیدی را بیشتر به‌کار ببرید.', $density));
        }

        return $this->result('density', 'چگالی کلمه کلیدی', 2, $max, 'warn', sprintf('چگالی زیاد (%.1f%%). از تکرار بیش از حد پرهیز کنید.', $density));
    }

    /** @return array{id: string, label: string, score: float, max: float, status: string, message: string} */
    private function result(string $id, string $label, float $score, float $max, string $status, string $message): array
    {
        return compact('id', 'label', 'score', 'max', 'status', 'message');
    }

    private function grade(int $score): string
    {
        return match (true) {
            $score >= 90 => 'عالی',
            $score >= 75 => 'خوب',
            $score >= 60 => 'متوسط',
            $score >= 40 => 'ضعیف',
            default => 'نیاز به بهبود جدی',
        };
    }

    private function summary(int $score): string
    {
        return match (true) {
            $score >= 90 => 'این پست از نظر سئو آماده انتشار در گوگل است.',
            $score >= 75 => 'پست خوب است؛ چند مورد کوچک را بهبود دهید.',
            $score >= 60 => 'پست قابل انتشار است ولی برای رتبه بهتر نیاز به اصلاح دارد.',
            default => 'قبل از انتشار، موارد قرمز و زرد را برطرف کنید.',
        };
    }

    private function plainText(string $html): string
    {
        $text = strip_tags($html);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim(preg_replace('/\s+/u', ' ', $text) ?? '');
    }

    private function firstKeyword(string $keywords): string
    {
        $parts = array_filter(array_map('trim', preg_split('/[,،]/u', $keywords) ?: []));

        return $parts[0] ?? '';
    }

    private function latinKeyword(string $keyword): string
    {
        return strtolower(preg_replace('/\s+/u', '-', trim($keyword)) ?? '');
    }
}
