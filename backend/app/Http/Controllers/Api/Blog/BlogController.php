<?php

namespace App\Http\Controllers\Api\Blog;

use App\Http\Controllers\Controller;
use App\Models\BlogAuthor;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\BlogTag;
use App\Services\Blog\BlogRelatedArticlesService;
use App\Services\Blog\BlogSearchService;
use App\Services\Blog\BlogSitemapService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Morilog\Jalali\Jalalian;

class BlogController extends Controller
{
    public const CATEGORIES = [
        'software' => 'نرم‌افزار و سامانه املاک',
        'crm' => 'CRM و فروش املاک',
        'filing' => 'فایلینگ و ثبت ملک',
        'agency' => 'مدیریت دفتر و آژانس',
        'accounting' => 'حسابداری و کمیسیون',
        'contracts' => 'قرارداد و حقوقی',
        'marketing' => 'بازاریابی املاک',
        'education' => 'آموزش مشاور املاک',
        'digital' => 'تحول دیجیتال',
        'ai' => 'هوش مصنوعی در املاک',
        'mobile' => 'اپلیکیشن موبایل',
        'website' => 'وبسایت اختصاصی',
        'bots' => 'ربات تلگرام و واتساپ',
        'reports' => 'گزارش و KPI',
        'security' => 'امنیت و OTP',
    ];

    public function __construct(
        private readonly BlogSearchService $searchService,
        private readonly BlogRelatedArticlesService $relatedService,
        private readonly BlogSitemapService $sitemapService,
        private readonly \App\Services\Seo\SeoInternalSearchLogger $internalSearchLogger,
        private readonly \App\Services\Cro\CroCtaResolver $ctaResolver,
    ) {}

    public function home(): JsonResponse
    {
        $latest = BlogPost::published()->orderByDesc('published_at')->limit(9)->get()->map(fn ($p) => $this->listItem($p));
        $featured = BlogPost::published()->where('is_featured', true)->orderByDesc('published_at')->limit(1)->get()->map(fn ($p) => $this->listItem($p));
        if ($featured->isEmpty()) {
            $featured = BlogPost::published()->orderByDesc('views')->limit(1)->get()->map(fn ($p) => $this->listItem($p));
        }
        $popular = $this->popularQuery()->limit(6)->get()->map(fn ($p) => $this->listItem($p));
        $editors = BlogPost::published()->where('is_editors_pick', true)->orderByDesc('published_at')->limit(4)->get()->map(fn ($p) => $this->listItem($p));

        return response()->json([
            'data' => [
                'featured' => $featured->first(),
                'latest' => $latest,
                'popular' => $popular,
                'editors_picks' => $editors,
                'categories' => $this->categoryPayload(),
            ],
        ]);
    }

    public function categories(): JsonResponse
    {
        return response()->json(['data' => $this->categoryPayload()]);
    }

    public function categoryShow(string $slug): JsonResponse
    {
        $cat = BlogCategory::query()->where('slug', $slug)->where('is_active', true)->first();
        $label = $cat?->name ?? (self::CATEGORIES[$slug] ?? null);
        if (! $label) {
            abort(404);
        }

        $posts = BlogPost::published()
            ->where('category_slug', $slug)
            ->orderByDesc('published_at')
            ->paginate(12);

        $featured = BlogPost::published()
            ->where('category_slug', $slug)
            ->where('is_featured', true)
            ->orderByDesc('published_at')
            ->first();

        return response()->json([
            'data' => [
                'category' => [
                    'slug' => $slug,
                    'label' => $label,
                    'description' => $cat?->description,
                    'seo_title' => $cat?->seo_title,
                    'meta_description' => $cat?->meta_description,
                    'featured_image' => $cat?->featured_image,
                    'is_indexable' => $cat?->is_indexable ?? true,
                ],
                'featured' => $featured ? $this->listItem($featured) : null,
                'posts' => $posts->map(fn (BlogPost $p) => $this->listItem($p)),
                'meta' => [
                    'current_page' => $posts->currentPage(),
                    'last_page' => $posts->lastPage(),
                    'total' => $posts->total(),
                ],
            ],
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $posts = BlogPost::published()
            ->when($request->input('category'), fn ($q, $cat) => $q->where('category_slug', $cat))
            ->when($request->input('pillar'), fn ($q, $p) => $q->where('pillar_slug', $p))
            ->when($request->input('tag'), function ($q, $tag) {
                $q->whereHas('tags', fn ($t) => $t->where('slug', $tag));
            })
            ->orderByDesc('published_at')
            ->paginate(min((int) $request->input('per_page', 12), 50));

        return response()->json([
            'data' => $posts->map(fn (BlogPost $post) => $this->listItem($post)),
            'meta' => [
                'current_page' => $posts->currentPage(),
                'last_page' => $posts->lastPage(),
                'total' => $posts->total(),
            ],
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $result = $this->searchService->search(
            (string) $request->input('q', ''),
            (int) $request->input('page', 1),
            (int) $request->input('per_page', 12),
        );

        try {
            $this->internalSearchLogger->log(
                (string) ($result['meta']['query'] ?? $request->input('q', '')),
                (int) ($result['meta']['total'] ?? 0),
                $request->ip()
            );
        } catch (\Throwable) {
            // Search must never fail because of analytics logging
        }

        return response()->json($result + [
            'seo' => ['robots' => 'noindex,follow'],
        ]);
    }

    public function show(string $slug): JsonResponse
    {
        $post = BlogPost::published()->where('slug', $slug)->firstOrFail();
        $this->incrementViewSafely($post, request());

        $related = $this->resolveRelated($post);

        $prev = BlogPost::published()
            ->where('published_at', '<', $post->published_at)
            ->orderByDesc('published_at')
            ->first();
        $next = BlogPost::published()
            ->where('published_at', '>', $post->published_at)
            ->orderBy('published_at')
            ->first();

        return response()->json([
            'data' => [
                ...$this->detailItem($post),
                'related' => $related,
                'previous' => $prev ? $this->listItem($prev) : null,
                'next' => $next ? $this->listItem($next) : null,
            ],
        ]);
    }

    public function preview(string $token): JsonResponse
    {
        $post = BlogPost::query()->where('preview_token', $token)->firstOrFail();

        return response()->json([
            'data' => [
                ...$this->detailItem($post),
                'related' => [],
                'is_preview' => true,
            ],
            'seo' => ['robots' => 'noindex,nofollow'],
        ]);
    }

    public function tagShow(string $slug): JsonResponse
    {
        $tag = BlogTag::query()->where('slug', $slug)->where('is_active', true)->firstOrFail();
        $posts = $tag->posts()->published()->orderByDesc('published_at')->paginate(12);

        return response()->json([
            'data' => [
                'tag' => [
                    'slug' => $tag->slug,
                    'name' => $tag->name,
                    'description' => $tag->description,
                    'is_indexable' => $tag->is_indexable,
                ],
                'posts' => $posts->map(fn (BlogPost $p) => $this->listItem($p)),
                'meta' => [
                    'current_page' => $posts->currentPage(),
                    'last_page' => $posts->lastPage(),
                    'total' => $posts->total(),
                ],
            ],
            'seo' => ['robots' => $tag->is_indexable ? 'index,follow' : 'noindex,follow'],
        ]);
    }

    public function authorShow(string $slug): JsonResponse
    {
        $author = BlogAuthor::query()->where('slug', $slug)->where('is_active', true)->firstOrFail();
        $posts = $author->posts()->published()->orderByDesc('published_at')->paginate(12);

        return response()->json([
            'data' => [
                'author' => [
                    'slug' => $author->slug,
                    'name' => $author->name,
                    'bio' => $author->bio,
                    'avatar' => $author->avatar,
                    'role' => $author->role,
                    'articles_count' => $author->posts()->published()->count(),
                    'is_indexable' => $author->is_indexable,
                ],
                'posts' => $posts->map(fn (BlogPost $p) => $this->listItem($p)),
                'meta' => [
                    'current_page' => $posts->currentPage(),
                    'last_page' => $posts->lastPage(),
                    'total' => $posts->total(),
                ],
            ],
        ]);
    }

    public function relatedSuggestions(string $slug): JsonResponse
    {
        $post = BlogPost::query()->where('slug', $slug)->firstOrFail();
        $items = $this->relatedService->suggest($post, 8)->map(fn ($row) => [
            ...$this->listItem($row['post']),
            'score' => $row['score'],
            'breakdown' => $row['breakdown'],
        ]);

        return response()->json(['data' => $items]);
    }

    public function sitemap(): JsonResponse
    {
        return response()->json($this->sitemapService->payload());
    }

    public function feed()
    {
        $posts = BlogPost::published()->orderByDesc('published_at')->limit(30)->get();
        $base = rtrim(config('app.frontend_url', config('app.url')), '/');
        $items = '';
        foreach ($posts as $post) {
            $link = $base.'/blog/'.$post->slug;
            $title = htmlspecialchars($post->title, ENT_XML1);
            $desc = htmlspecialchars((string) ($post->excerpt ?: ''), ENT_XML1);
            $date = ($post->published_at ?? now())->toRfc2822String();
            $author = htmlspecialchars((string) ($post->author_name ?: 'تیم پوشه'), ENT_XML1);
            $updated = ($post->content_updated_at ?? $post->updated_at ?? $post->published_at)?->toRfc2822String();
            $items .= "<item><title>{$title}</title><link>{$link}</link><guid>{$link}</guid><description>{$desc}</description><pubDate>{$date}</pubDate><author>{$author}</author><lastBuildDate>{$updated}</lastBuildDate></item>\n";
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?><rss version="2.0"><channel>'
            .'<title>وبلاگ پوشه</title>'
            .'<link>'.$base.'/blog</link>'
            .'<description>مرجع فارسی نرم‌افزار و مدیریت املاک</description>'
            .'<language>fa-IR</language>'
            .$items
            .'</channel></rss>';

        return response($xml, 200, ['Content-Type' => 'application/rss+xml; charset=UTF-8']);
    }

    /** @return list<array<string, mixed>> */
    private function categoryPayload(): array
    {
        $dbCats = BlogCategory::query()->where('is_active', true)->orderBy('sort_order')->get();
        $counts = BlogPost::published()
            ->selectRaw('category_slug, count(*) as total')
            ->whereNotNull('category_slug')
            ->groupBy('category_slug')
            ->pluck('total', 'category_slug');

        if ($dbCats->isNotEmpty()) {
            return $dbCats->map(fn (BlogCategory $cat) => [
                'slug' => $cat->slug,
                'label' => $cat->name,
                'description' => $cat->description,
                'count' => (int) ($counts[$cat->slug] ?? 0),
                'url' => '/blog/category/'.$cat->slug,
            ])->values()->all();
        }

        return collect(self::CATEGORIES)->map(fn ($label, $slug) => [
            'slug' => $slug,
            'label' => $label,
            'count' => (int) ($counts[$slug] ?? 0),
            'url' => '/blog/category/'.$slug,
        ])->values()->all();
    }

    private function popularQuery()
    {
        $days = (int) config('blog.popular.days_window', 90);

        return BlogPost::published()
            ->where('published_at', '>=', now()->subDays($days))
            ->orderByDesc('view_score')
            ->orderByDesc('views');
    }

    /** @return list<array<string, mixed>> */
    private function resolveRelated(BlogPost $post): array
    {
        if ($post->related_slugs) {
            $manual = BlogPost::published()
                ->whereIn('slug', $post->related_slugs)
                ->get()
                ->map(fn (BlogPost $p) => $this->listItem($p))
                ->values()
                ->all();
            if (count($manual) >= 3) {
                return $manual;
            }
        }

        return $this->relatedService->suggest($post, 6)
            ->map(fn ($row) => $this->listItem($row['post']))
            ->all();
    }

    private function incrementViewSafely(BlogPost $post, Request $request): void
    {
        $ua = strtolower((string) $request->userAgent());
        if (preg_match('/bot|crawl|spider|slurp|facebookexternalhit|preview/i', $ua)) {
            return;
        }

        $key = 'blog.view.'.$post->id.'.'.sha1(($request->ip() ?: 'x').'|'.$ua);
        if (cache()->add($key, 1, now()->addMinutes(30))) {
            $post->increment('views');
            $ageDays = max(1, Carbon::now()->diffInDays($post->published_at ?? now()));
            $score = (int) round(($post->views + 1) * (1 + 30 / $ageDays));
            $post->update(['view_score' => $score]);
        }
    }

    private function listItem(BlogPost $post): array
    {
        return [
            'slug' => $post->slug,
            'title' => $post->title,
            'excerpt' => $post->excerpt,
            'cover_image' => $post->cover_image,
            'category_slug' => $post->category_slug,
            'category_label' => $post->category_label,
            'pillar_slug' => $post->pillar_slug,
            'author_name' => $post->author_name,
            'reading_time' => $post->reading_time,
            'views' => $post->views,
            'is_featured' => (bool) $post->is_featured,
            'published_at' => $post->published_at?->toIso8601String(),
            'updated_at' => ($post->content_updated_at ?? $post->updated_at)?->toIso8601String(),
            'published_at_jalali' => $post->published_at
                ? Jalalian::fromDateTime($post->published_at)->format('Y/m/d')
                : null,
            'updated_at_jalali' => ($post->content_updated_at ?? $post->updated_at)
                ? Jalalian::fromDateTime($post->content_updated_at ?? $post->updated_at)->format('Y/m/d')
                : null,
        ];
    }

    private function detailItem(BlogPost $post): array
    {
        return [
            ...$this->listItem($post),
            'content' => $post->content,
            'meta_title' => $post->meta_title ?? $post->title,
            'meta_description' => $post->meta_description ?? $post->excerpt,
            'og_title' => $post->og_title,
            'og_description' => $post->og_description,
            'og_image' => $post->og_image ?: $post->cover_image,
            'canonical_url' => $post->canonical_url,
            'robots_directive' => $post->robots_directive,
            'keywords' => $post->keywords,
            'focus_keyword' => $post->focus_keyword,
            'faq' => $post->faq ?? [],
            'cta_text' => $post->cta_text,
            'cta_url' => $post->cta_url,
            'search_intent' => $post->search_intent,
            'business_intent' => $post->business_intent,
            'funnel_stage' => $post->funnel_stage,
            'cro' => $this->ctaResolver->resolveForPost($post),
        ];
    }
}
