<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Api\Blog\BlogController;
use App\Models\BlogPost;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Morilog\Jalali\Jalalian;

class BlogWebController extends Controller
{
    private function siteUrl(): string
    {
        return rtrim(config('app.frontend_url', config('app.url')), '/');
    }

    public function index(): View
    {
        $posts = BlogPost::published()
            ->orderByDesc('published_at')
            ->limit(50)
            ->get();

        $title = 'وبلاگ املاک و نرم‌افزار مدیریت دفتر | پوشه';
        $description = 'مرجع فارسی نرم‌افزار املاک، CRM مشاوران، ثبت ملک، حسابداری دفتر و تحول دیجیتال املاک در ایران.';

        return view('blog.index', [
            'posts' => $posts,
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

    public function category(string $slug): View|Response
    {
        if (! isset(BlogController::CATEGORIES[$slug])) {
            abort(404);
        }

        $posts = BlogPost::published()
            ->where('category_slug', $slug)
            ->orderByDesc('published_at')
            ->limit(50)
            ->get();

        if ($posts->isEmpty()) {
            abort(404);
        }

        $label = BlogController::CATEGORIES[$slug];
        $title = "{$label} | وبلاگ پوشه";
        $description = "مقالات {$label} — راهنمای تخصصی برای مشاوران و دفاتر املاک.";

        return view('blog.category', [
            'posts' => $posts,
            'categorySlug' => $slug,
            'categoryLabel' => $label,
            'seo' => $this->seo($title, $description, "/blog/category/{$slug}"),
            'jsonLd' => [
                $this->organizationLd(),
                [
                    '@context' => 'https://schema.org',
                    '@type' => 'CollectionPage',
                    'name' => $label,
                    'url' => $this->siteUrl()."/blog/category/{$slug}",
                    'inLanguage' => 'fa-IR',
                ],
            ],
        ]);
    }

    public function show(string $slug): View|Response
    {
        $post = BlogPost::published()->where('slug', $slug)->first();

        if (! $post) {
            return response()->view('blog.not-found', [
                'seo' => $this->seo('مقاله یافت نشد', 'مقاله مورد نظر یافت نشد.', '/blog', noindex: true),
            ], 404);
        }

        $post->increment('views');

        $related = [];
        if ($post->related_slugs) {
            $related = BlogPost::published()
                ->whereIn('slug', $post->related_slugs)
                ->limit(5)
                ->get();
        }

        $title = ($post->meta_title ?: $post->title).' | پوشه';
        $description = $post->meta_description ?: $post->excerpt ?: '';
        $image = $post->cover_image ? (str_starts_with($post->cover_image, 'http') ? $post->cover_image : $this->siteUrl().$post->cover_image) : null;

        $publishedIso = $post->published_at?->toIso8601String();
        $updatedIso = $post->updated_at?->toIso8601String();

        return view('blog.show', [
            'post' => $post,
            'related' => $related,
            'publishedJalali' => $post->published_at ? Jalalian::fromDateTime($post->published_at)->format('Y/m/d') : null,
            'seo' => $this->seo($title, $description, "/blog/{$post->slug}", image: $image, type: 'article'),
            'jsonLd' => array_values(array_filter([
                [
                    '@context' => 'https://schema.org',
                    '@type' => 'Article',
                    'headline' => $post->title,
                    'description' => $description,
                    'image' => $image ? [$image] : null,
                    'author' => ['@type' => 'Organization', 'name' => $post->author_name ?: 'تیم پوشه'],
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
        $body .= "Allow: /\nAllow: /blog\nAllow: /blog/\nAllow: /register\nAllow: /download\n";
        $body .= "Disallow: /dashboard\nDisallow: /properties\nDisallow: /settings\nDisallow: /admin\nDisallow: /api/\n\n";
        $body .= "Sitemap: {$base}/sitemap.xml\n";

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
}
