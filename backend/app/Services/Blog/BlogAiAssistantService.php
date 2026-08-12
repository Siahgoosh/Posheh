<?php

namespace App\Services\Blog;

use App\Models\BlogPost;
use Illuminate\Support\Str;

/**
 * Local writing assistant — never auto-publishes.
 * Uses deterministic heuristics; external LLM stays optional/off by default.
 */
class BlogAiAssistantService
{
    public const PROMPT_VERSION = 'CONTENT_WRITER_V1';

    public function __construct(
        private readonly PersianTextNormalizer $normalizer,
        private readonly BlogRelatedArticlesService $related,
    ) {}

    /** @param array<string, mixed> $payload @return array<string, mixed> */
    public function assist(string $action, array $payload): array
    {
        $title = trim((string) ($payload['title'] ?? ''));
        $content = (string) ($payload['content'] ?? '');
        $plain = trim(preg_replace('/\s+/u', ' ', strip_tags($content)) ?? '');
        $focus = trim((string) ($payload['focus_keyword'] ?? $payload['keywords'] ?? ''));
        $intent = (string) ($payload['search_intent'] ?? 'informational');
        $category = (string) ($payload['category_slug'] ?? '');

        return match ($action) {
            'outline' => ['action' => $action, 'prompt_version' => self::PROMPT_VERSION, 'result' => $this->outline($title, $focus, $intent)],
            'titles' => ['action' => $action, 'prompt_version' => self::PROMPT_VERSION, 'result' => $this->titles($title, $focus, $intent)],
            'meta_description' => ['action' => $action, 'prompt_version' => self::PROMPT_VERSION, 'result' => $this->metaDescription($title, $plain, $focus)],
            'excerpt' => ['action' => $action, 'prompt_version' => self::PROMPT_VERSION, 'result' => $this->excerpt($plain, $title)],
            'slug' => ['action' => $action, 'prompt_version' => self::PROMPT_VERSION, 'result' => $this->slugSuggest($title, $focus)],
            'faq' => ['action' => $action, 'prompt_version' => self::PROMPT_VERSION, 'result' => $this->faq($title, $focus, $plain)],
            'brief' => ['action' => $action, 'prompt_version' => self::PROMPT_VERSION, 'result' => $this->brief($title, $focus, $intent, $category)],
            'intro' => ['action' => $action, 'prompt_version' => self::PROMPT_VERSION, 'result' => $this->intro($title, $focus)],
            'conclusion' => ['action' => $action, 'prompt_version' => self::PROMPT_VERSION, 'result' => $this->conclusion($title)],
            'simplify' => ['action' => $action, 'prompt_version' => self::PROMPT_VERSION, 'result' => $this->simplify($payload['selection'] ?? $plain)],
            'expand' => ['action' => $action, 'prompt_version' => self::PROMPT_VERSION, 'result' => $this->expand($payload['selection'] ?? $plain, $focus)],
            'cta' => ['action' => $action, 'prompt_version' => self::PROMPT_VERSION, 'result' => $this->cta($intent)],
            'normalize_persian' => ['action' => $action, 'prompt_version' => 'CONTENT_EDITOR_V1', 'result' => ['text' => $this->normalizer->normalize((string) ($payload['selection'] ?? $plain)), 'note' => 'Preview only — apply manually / with undo']],
            'internal_links' => ['action' => $action, 'prompt_version' => 'SEO_ANALYZER_V1', 'result' => $this->internalLinks($payload)],
            'cannibalization_check' => ['action' => $action, 'prompt_version' => 'SEO_ANALYZER_V1', 'result' => $this->cannibalizationCheck($title, $focus, (int) ($payload['exclude_id'] ?? 0))],
            'intent_suggest' => ['action' => $action, 'prompt_version' => 'SEO_ANALYZER_V1', 'result' => $this->intentSuggest($title, $plain)],
            default => ['action' => $action, 'error' => 'Unknown action', 'available' => $this->availableActions()],
        };
    }

    /** @return list<string> */
    public function availableActions(): array
    {
        return [
            'outline', 'titles', 'meta_description', 'excerpt', 'slug', 'faq', 'brief',
            'intro', 'conclusion', 'simplify', 'expand', 'cta', 'normalize_persian',
            'internal_links', 'cannibalization_check', 'intent_suggest',
        ];
    }

    /** @return array<string, mixed> */
    private function outline(string $title, string $focus, string $intent): array
    {
        $topic = $focus ?: $title ?: 'موضوع';

        return [
            'h1' => $title ?: 'راهنمای '.$topic,
            'sections' => [
                ['h2' => 'پاسخ کوتاه', 'notes' => 'Answer-first در ۲–۳ جمله'],
                ['h2' => $topic.' چیست؟', 'notes' => 'تعریف عملی بدون کلیشه'],
                ['h2' => 'چه زمانی مهم است؟', 'notes' => 'مخاطب دفتر/مشاور'],
                ['h2' => 'چک‌لیست عملی', 'notes' => 'گام‌به‌گام'],
                ['h2' => 'اشتباهات رایج', 'notes' => '۳–۵ مورد'],
                ['h2' => 'سوالات متداول', 'notes' => 'FAQ واقعی'],
                ['h2' => 'جمع‌بندی', 'notes' => 'بدون اغراق'],
            ],
            'intent' => $intent,
            'disclaimer' => 'پیشنهاد ساختاری است — قبل از انتشار بازبینی انسانی لازم است.',
        ];
    }

    /** @return list<string> */
    private function titles(string $title, string $focus, string $intent): array
    {
        $base = $focus ?: $title ?: 'موضوع';
        $out = array_values(array_unique(array_filter([
            $title,
            'راهنمای عملی '.$base,
            $base.'؛ چک‌لیست برای مشاور املاک',
            'چطور '.$base.' را بدون اشتباه انجام دهیم؟',
            $intent === 'commercial' ? $base.' — معیارهای انتخاب واقعی' : null,
        ])));

        return array_slice($out, 0, 5);
    }

    /** @return array{text: string, note: string} */
    private function metaDescription(string $title, string $plain, string $focus): array
    {
        $seed = $plain !== '' ? mb_substr($plain, 0, 140) : ($title.' — راهنمای عملی برای مشاوران املاک.');
        $text = trim($seed);
        if ($focus && ! str_contains($this->normalizer->normalize($text), $this->normalizer->normalize($focus))) {
            $text = $focus.' | '.$text;
        }
        $text = mb_substr($text, 0, 160);

        return ['text' => $text, 'note' => 'فقط از محتوای موجود/عنوان ساخته شده — ادعا اضافه نشده است.'];
    }

    /** @return array{text: string} */
    private function excerpt(string $plain, string $title): array
    {
        $text = $plain !== '' ? mb_substr($plain, 0, 180) : $title;

        return ['text' => rtrim($text, ' .،,').'…'];
    }

    /** @return array{slug: string, warning: string|null} */
    private function slugSuggest(string $title, string $focus): array
    {
        $raw = $focus ?: $title;
        // Prefer ascii slug from latin tokens if present; else transliterate-ish keep dashes
        $slug = Str::slug($raw);
        if ($slug === '') {
            $slug = 'article-'.Str::lower(Str::random(6));
        }

        return [
            'slug' => $slug,
            'warning' => 'اگر مقاله منتشر شده، تغییر slug بدون ۳۰۱ ممنوع است.',
        ];
    }

    /** @return list<array{question: string, answer: string}> */
    private function faq(string $title, string $focus, string $plain): array
    {
        $topic = $focus ?: $title ?: 'این موضوع';

        return [
            ['question' => $topic.' برای چه کسانی مفید است؟', 'answer' => 'برای مشاوران و دفاتری که می‌خواهند فرآیند را شفاف و قابل پیگیری کنند — نه وعده معجزه.'],
            ['question' => 'آیا این متن جایگزین مشاوره تخصصی است؟', 'answer' => 'خیر. برای موارد حقوقی/مالیاتی به متخصص مراجعه کنید.'],
            ['question' => 'از کجا شروع کنیم؟', 'answer' => mb_substr($plain !== '' ? $plain : 'با یک چک‌لیست کوتاه و ثبت منظم داده شروع کنید.', 0, 160)],
        ];
    }

    /** @return array<string, mixed> */
    private function brief(string $title, string $focus, string $intent, string $category): array
    {
        return [
            'primary_topic' => $focus ?: $title,
            'search_intent' => $intent,
            'audience' => 'مشاور / مدیر دفتر املاک',
            'user_problem' => 'نبود فرآیند شفاف و اتلاف زمان روی کارهای پراکنده',
            'recommended_h1' => $title ?: ('راهنمای '.($focus ?: 'موضوع')),
            'h2_structure' => array_column($this->outline($title, $focus, $intent)['sections'], 'h2'),
            'related_questions' => array_column($this->faq($title, $focus, ''), 'question'),
            'entities' => array_values(array_filter([$focus, $category, 'دفتر املاک', 'مشاور'])),
            'internal_links' => ['پیشنهاد پس از انتخاب دسته از مقالات موجود'],
            'external_sources' => ['فقط منبع رسمی — آمار جعلی ممنوع'],
            'cta' => $this->cta($intent),
            'content_goal' => 'رفع مشکل کاربر + اعتماد + مسیر نرم به محصول/تماس',
            'human_review_required' => true,
        ];
    }

    /** @return array{html: string} */
    private function intro(string $title, string $focus): array
    {
        $topic = $focus ?: $title ?: 'این موضوع';

        return [
            'html' => '<p><strong>پاسخ کوتاه:</strong> '.$topic.' وقتی مفید است که فرآیند دفتر را شفاف‌تر و قابل پیگیری کند — نه وقتی فقط ظاهر مدرن داشته باشد.</p>',
        ];
    }

    /** @return array{html: string} */
    private function conclusion(string $title): array
    {
        return [
            'html' => '<p>جمع‌بندی: '.$title.' را با چک‌لیست کوتاه شروع کنید، داده را منظم ثبت کنید و از آمار/ادعای بدون منبع پرهیز کنید.</p>',
        ];
    }

    /** @return array{text: string} */
    private function simplify(string $text): array
    {
        $t = strip_tags($text);
        $t = preg_replace('/\s+/u', ' ', $t) ?? $t;

        return ['text' => trim($t)];
    }

    /** @return array{html: string} */
    private function expand(string $text, string $focus): array
    {
        $base = trim(strip_tags($text));
        $extra = $focus ? ' در عمل برای «'.$focus.'» بهتر است مثال واقعی دفتر خودتان را بنویسید.' : ' مثال واقعی از فرآیند دفتر اضافه کنید.';

        return ['html' => '<p>'.$base.$extra.'</p><ul><li>گام ۱ را مشخص کنید</li><li>معیار موفقیت را بنویسید</li><li>اشتباه رایج را ذکر کنید</li></ul>'];
    }

    /** @return array{cta_text: string, cta_url: string} */
    private function cta(string $intent): array
    {
        return match ($intent) {
            'commercial', 'transactional' => ['cta_text' => 'آشنایی کوتاه با پوشه', 'cta_url' => '/register'],
            default => ['cta_text' => 'مطالب مرتبط را ببینید', 'cta_url' => '/blog'],
        };
    }

    /** @param array<string, mixed> $payload @return list<array<string, mixed>> */
    private function internalLinks(array $payload): array
    {
        $post = new BlogPost($payload);
        if (! empty($payload['id'])) {
            $existing = BlogPost::query()->find($payload['id']);
            if ($existing) {
                $post = $existing;
            }
        }
        $suggestions = $this->related->suggest($post, 5);

        return collect($suggestions)->map(fn ($s) => [
            'slug' => $s['post']->slug ?? ($s['slug'] ?? null),
            'title' => $s['post']->title ?? ($s['title'] ?? null),
            'reason' => $s['reason'] ?? 'related',
            'score' => $s['score'] ?? null,
            'suggested_anchor' => $s['post']->title ?? ($s['title'] ?? ($s['slug'] ?? 'مقاله مرتبط')),
        ])->all();
    }

    /** @return array{risk: string, matches: list<array<string,mixed>>, recommendation: string} */
    private function cannibalizationCheck(string $title, string $focus, int $excludeId): array
    {
        $needle = $this->normalizer->normalize($focus ?: $title);
        if ($needle === '') {
            return ['risk' => 'unknown', 'matches' => [], 'recommendation' => 'عنوان/کلمه تمرکز را وارد کنید.'];
        }
        $matches = BlogPost::query()
            ->when($excludeId > 0, fn ($q) => $q->where('id', '!=', $excludeId))
            ->where(function ($q) use ($needle, $focus, $title) {
                $q->where('focus_keyword', 'like', '%'.mb_substr($focus ?: $title, 0, 40).'%')
                    ->orWhere('title', 'like', '%'.mb_substr($title ?: $focus, 0, 40).'%')
                    ->orWhere('slug', 'like', '%'.Str::slug(mb_substr($focus ?: $title, 0, 40)).'%');
            })
            ->limit(8)
            ->get(['id', 'slug', 'title', 'is_published', 'focus_keyword']);

        $risk = $matches->count() >= 2 ? 'high' : ($matches->count() === 1 ? 'medium' : 'low');

        return [
            'risk' => $risk,
            'matches' => $matches->map(fn ($m) => [
                'id' => $m->id,
                'slug' => $m->slug,
                'title' => $m->title,
                'is_published' => $m->is_published,
                'focus_keyword' => $m->focus_keyword,
            ])->all(),
            'recommendation' => $risk === 'low'
                ? 'مقاله مشابه قوی پیدا نشد — می‌توانید ادامه دهید.'
                : 'احتمال cannibalization — ترجیحاً UPDATE EXISTING ARTICLE را بررسی کنید.',
        ];
    }

    /** @return array{intent: string, confidence: string} */
    private function intentSuggest(string $title, string $plain): array
    {
        $n = $this->normalizer->normalize($title.' '.$plain);
        if (str_contains($n, 'قیمت') || str_contains($n, 'خرید نرم') || str_contains($n, 'مقایسه')) {
            return ['intent' => 'commercial', 'confidence' => 'medium'];
        }
        if (str_contains($n, 'ثبت نام') || str_contains($n, 'دانلود')) {
            return ['intent' => 'transactional', 'confidence' => 'medium'];
        }

        return ['intent' => 'informational', 'confidence' => 'low'];
    }
}
