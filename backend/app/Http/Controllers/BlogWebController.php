<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Api\Blog\BlogController;
use App\Models\BlogAuthor;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\BlogTag;
use App\Services\Blog\BlogSearchService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Morilog\Jalali\Jalalian;

class BlogWebController extends Controller
{
    public function __construct(
        private readonly BlogSearchService $searchService,
    ) {}

    private function siteUrl(): string
    {
        return rtrim(config('app.frontend_url', config('app.url')), '/');
    }

    public function index(): View
    {
        $posts = BlogPost::published()->orderByDesc('published_at')->paginate(12);
        $featured = BlogPost::published()->where('is_featured', true)->orderByDesc('published_at')->first()
            ?: BlogPost::published()->orderByDesc('views')->first();
        $popular = BlogPost::published()->orderByDesc('view_score')->orderByDesc('views')->limit(5)->get();

        $title = 'وبلاگ املاک و نرم‌افزار مدیریت دفتر | پوشه';
        $description = 'مرجع فارسی نرم‌افزار املاک، CRM مشاوران، ثبت ملک، حسابداری دفتر و تحول دیجیتال املاک در ایران.';

        return view('blog.index', [
            'posts' => $posts,
            'featured' => $featured,
            'popular' => $popular,
            'categories' => BlogController::CATEGORIES,
            'seo' => $this->seo($title, $description, '/blog'),
            'jsonLd' => [
                $this->organizationLd(),
                $this->websiteLd(),
                [
                    '@context' => 'https://schema.org',
                    '@type' => 'CollectionPage',
                    'name' => 'وبلاگ پوشه',
                    'url' => $this->siteUrl().'/blog',
                    'inLanguage' => 'fa-IR',
                ],
            ],
        ]);
    }

    public function search(Request $request): View
    {
        $q = (string) $request->input('q', '');
        $result = $this->searchService->search($q, (int) $request->input('page', 1), 12);

        return view('blog.search', [
            'query' => $q,
            'results' => $result['data'],
            'meta' => $result['meta'],
            'seo' => array_merge($this->seo('جستجو در وبلاگ', 'نتایج جستجوی وبلاگ پوشه', '/blog/search'), [
                'noindex' => true,
                'robots' => 'noindex,follow',
            ]),
            'jsonLd' => [],
        ]);
    }

    public function category(string $slug): View|Response
    {
        $cat = BlogCategory::query()->where('slug', $slug)->where('is_active', true)->first();
        $label = $cat?->name ?? (BlogController::CATEGORIES[$slug] ?? null);
        if (! $label) {
            abort(404);
        }

        $posts = BlogPost::published()
            ->where('category_slug', $slug)
            ->orderByDesc('published_at')
            ->paginate(12);

        if ($posts->total() < 1 && ! $cat) {
            abort(404);
        }

        $title = ($cat?->seo_title) ?: "{$label} | وبلاگ پوشه";
        $description = $cat?->meta_description ?: "مقالات {$label} — راهنمای تخصصی برای مشاوران و دفاتر املاک.";

        return view('blog.category', [
            'posts' => $posts,
            'categorySlug' => $slug,
            'categoryLabel' => $label,
            'categoryDescription' => $cat?->description,
            'seo' => array_merge($this->seo($title, $description, "/blog/category/{$slug}", noindex: ! ($cat?->is_indexable ?? true)), [
                'robots' => ($cat?->is_indexable ?? true) ? 'index,follow' : 'noindex,follow',
            ]),
            'jsonLd' => [
                $this->organizationLd(),
                [
                    '@context' => 'https://schema.org',
                    '@type' => 'CollectionPage',
                    'name' => $label,
                    'url' => $this->siteUrl()."/blog/category/{$slug}",
                    'inLanguage' => 'fa-IR',
                ],
                $this->breadcrumbLd([
                    ['name' => 'خانه', 'url' => $this->siteUrl().'/'],
                    ['name' => 'وبلاگ', 'url' => $this->siteUrl().'/blog'],
                    ['name' => $label, 'url' => $this->siteUrl()."/blog/category/{$slug}"],
                ]),
            ],
        ]);
    }

    public function tag(string $slug): View|Response
    {
        $tag = BlogTag::query()->where('slug', $slug)->where('is_active', true)->firstOrFail();
        $posts = $tag->posts()->published()->orderByDesc('published_at')->paginate(12);

        return view('blog.tag', [
            'tag' => $tag,
            'posts' => $posts,
            'seo' => array_merge($this->seo($tag->seo_title ?: $tag->name, $tag->meta_description ?: ($tag->description ?: ''), "/blog/tag/{$slug}", noindex: ! $tag->is_indexable), [
                'robots' => $tag->is_indexable ? 'index,follow' : 'noindex,follow',
            ]),
            'jsonLd' => [
                $this->breadcrumbLd([
                    ['name' => 'خانه', 'url' => $this->siteUrl().'/'],
                    ['name' => 'وبلاگ', 'url' => $this->siteUrl().'/blog'],
                    ['name' => $tag->name, 'url' => $this->siteUrl()."/blog/tag/{$slug}"],
                ]),
            ],
        ]);
    }

    public function author(string $slug): View|Response
    {
        $author = BlogAuthor::query()->where('slug', $slug)->where('is_active', true)->firstOrFail();
        $posts = $author->posts()->published()->orderByDesc('published_at')->paginate(12);

        return view('blog.author', [
            'author' => $author,
            'posts' => $posts,
            'seo' => array_merge($this->seo($author->name, $author->bio ?: 'نویسنده وبلاگ پوشه', "/blog/author/{$slug}", noindex: ! $author->is_indexable), [
                'robots' => $author->is_indexable ? 'index,follow' : 'noindex,follow',
            ]),
            'jsonLd' => array_values(array_filter([
                [
                    '@context' => 'https://schema.org',
                    '@type' => 'Person',
                    'name' => $author->name,
                    'description' => $author->bio,
                    'url' => $this->siteUrl()."/blog/author/{$slug}",
                ],
                $this->breadcrumbLd([
                    ['name' => 'خانه', 'url' => $this->siteUrl().'/'],
                    ['name' => 'وبلاگ', 'url' => $this->siteUrl().'/blog'],
                    ['name' => $author->name, 'url' => $this->siteUrl()."/blog/author/{$slug}"],
                ]),
            ])),
        ]);
    }

    public function preview(string $token): View|Response
    {
        $post = BlogPost::query()->where('preview_token', $token)->firstOrFail();

        return view('blog.show', [
            'post' => $post,
            'related' => collect(),
            'publishedJalali' => $post->published_at ? Jalalian::fromDateTime($post->published_at)->format('Y/m/d') : null,
            'seo' => array_merge($this->seo($post->title, (string) $post->excerpt, '/blog/preview/'.$token, noindex: true), [
                'robots' => 'noindex,nofollow',
            ]),
            'jsonLd' => [],
            'isPreview' => true,
        ]);
    }

    public function show(string $slug): View|Response
    {
        $post = BlogPost::published()->where('slug', $slug)->first();

        if (! $post) {
            $popular = BlogPost::published()->orderByDesc('views')->limit(6)->get();

            return response()->view('blog.not-found', [
                'popular' => $popular,
                'seo' => $this->seo('مقاله یافت نشد', 'مقاله مورد نظر یافت نشد.', '/blog', noindex: true),
            ], 404);
        }

        $post->increment('views');

        $related = collect();
        if ($post->related_slugs) {
            $related = BlogPost::published()->whereIn('slug', $post->related_slugs)->limit(5)->get();
        }

        $metaTitle = $post->meta_title ?: $post->title;
        $title = str_contains($metaTitle, 'پوشه') ? $metaTitle : $metaTitle.' | پوشه';
        $description = $post->meta_description ?: $post->excerpt ?: '';
        $image = $post->og_image ?: $post->cover_image;
        $image = $image ? (str_starts_with($image, 'http') ? $image : $this->siteUrl().$image) : null;
        $robots = $post->robots_directive ?: 'index,follow';
        $noindex = str_contains(strtolower($robots), 'noindex');
        $canonical = $post->canonical_url
            ? (str_starts_with($post->canonical_url, 'http') ? $post->canonical_url : $this->siteUrl().$post->canonical_url)
            : $this->siteUrl()."/blog/{$post->slug}";

        $publishedIso = $post->published_at?->toIso8601String();
        $updatedIso = ($post->content_updated_at ?? $post->updated_at)?->toIso8601String();
        $seo = $this->seo($title, $description, "/blog/{$post->slug}", image: $image, type: 'article', noindex: $noindex);
        $seo['keywords'] = $post->keywords;
        $seo['robots'] = $robots;
        $seo['canonical'] = $canonical;
        $seo['url'] = $canonical;
        $seo['ogTitle'] = $post->og_title ?: $title;
        $seo['ogDescription'] = $post->og_description ?: $description;

        return view('blog.show', [
            'post' => $post,
            'related' => $related,
            'publishedJalali' => $post->published_at ? Jalalian::fromDateTime($post->published_at)->format('Y/m/d') : null,
            'updatedJalali' => ($post->content_updated_at ?? $post->updated_at)
                ? Jalalian::fromDateTime($post->content_updated_at ?? $post->updated_at)->format('Y/m/d')
                : null,
            'seo' => array_merge($seo, [
                'publishedTime' => $publishedIso,
                'modifiedTime' => $updatedIso ?: $publishedIso,
            ]),
            'jsonLd' => array_values(array_filter([
                [
                    '@context' => 'https://schema.org',
                    '@type' => 'BlogPosting',
                    'headline' => $post->title,
                    'description' => $description,
                    'image' => $image ? [$image] : null,
                    'author' => ['@type' => 'Person', 'name' => $post->author_name ?: 'تیم پوشه'],
                    'publisher' => [
                        '@type' => 'Organization',
                        'name' => 'پوشه',
                        'logo' => ['@type' => 'ImageObject', 'url' => $this->siteUrl().'/favicon.svg'],
                    ],
                    'datePublished' => $publishedIso,
                    'dateModified' => $updatedIso ?: $publishedIso,
                    'mainEntityOfPage' => $this->siteUrl()."/blog/{$post->slug}",
                    'articleSection' => $post->category_label,
                    'inLanguage' => 'fa-IR',
                ],
                $this->breadcrumbLd([
                    ['name' => 'خانه', 'url' => $this->siteUrl().'/'],
                    ['name' => 'وبلاگ', 'url' => $this->siteUrl().'/blog'],
                    ...($post->category_label ? [['name' => $post->category_label, 'url' => $this->siteUrl().'/blog/category/'.$post->category_slug]] : []),
                    ['name' => $post->title, 'url' => $this->siteUrl()."/blog/{$post->slug}"],
                ]),
                $this->faqLd($post->faq ?? []),
            ])),
        ]);
    }

    public function robots(): Response
    {
        $base = $this->siteUrl();
        $body = "User-agent: *\n";
        $body .= "Allow: /\nAllow: /blog\nAllow: /blog/\nAllow: /register\nAllow: /download\nAllow: /contact\nAllow: /feed\n";
        $body .= "Allow: /tour/\nAllow: /p/\nAllow: /o/\n";
        $body .= "Disallow: /dashboard\nDisallow: /properties\nDisallow: /settings\nDisallow: /admin\nDisallow: /api/\n";
        $body .= "Disallow: /team-chat\nDisallow: /tickets\nDisallow: /accounting\nDisallow: /crm\nDisallow: /embed/\n";
        $body .= "Disallow: /blog/search\nDisallow: /blog/preview/\n\n";
        $body .= "Sitemap: {$base}/sitemap.xml\n";
        $body .= "Sitemap: {$base}/sitemap-blog.xml\n";
        $body .= "Sitemap: {$base}/sitemap-pages.xml\n";
        $body .= "Sitemap: {$base}/sitemap-tours.xml\n";

        return response($body, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    /** @return array<string, mixed> */
    private function seo(string $title, string $description, string $path, ?string $image = null, string $type = 'website', bool $noindex = false): array
    {
        $url = $this->siteUrl().$path;
        $ogImage = $image ?: $this->siteUrl().'/og-default.png';

        return compact('title', 'description', 'url', 'ogImage', 'type', 'noindex');
    }

    /** @return array<string, mixed> */
    private function organizationLd(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => 'پوشه',
            'url' => $this->siteUrl(),
            'logo' => $this->siteUrl().'/favicon.svg',
            'inLanguage' => 'fa-IR',
        ];
    }

    /** @return array<string, mixed> */
    private function websiteLd(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => 'پوشه',
            'url' => $this->siteUrl(),
            'inLanguage' => 'fa-IR',
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => $this->siteUrl().'/blog/search?q={search_term_string}',
                'query-input' => 'required name=search_term_string',
            ],
        ];
    }

    /** @param array<int, array{name: string, url: string}> $items */
    private function breadcrumbLd(array $items): ?array
    {
        if ($items === []) {
            return null;
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => collect($items)->values()->map(fn ($item, $i) => [
                '@type' => 'ListItem',
                'position' => $i + 1,
                'name' => $item['name'],
                'item' => $item['url'],
            ])->all(),
        ];
    }

    /** @param array<int, array{question?: string, answer?: string}> $faq */
    private function faqLd(array $faq): ?array
    {
        $items = collect($faq)->filter(fn ($f) => ! empty($f['question']) && ! empty($f['answer']))->values();
        if ($items->isEmpty()) {
            return null;
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => $items->map(fn ($f) => [
                '@type' => 'Question',
                'name' => $f['question'],
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f['answer']],
            ])->all(),
        ];
    }

    private function pageTitle(string $title): string
    {
        $trimmed = trim($title);
        if ($trimmed === '') {
            return 'پوشه';
        }
        if (str_contains($trimmed, 'پوشه')) {
            return $trimmed;
        }

        return $trimmed.' | پوشه';
    }
}
