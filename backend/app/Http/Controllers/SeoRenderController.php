<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Api\Blog\BlogController;
use App\Models\BlogPost;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SeoRenderController extends Controller
{
    public function home(): View
    {
        return view('seo.page', [
            'title' => 'پوشه | سامانه مدیریت املاک',
            'description' => 'پوشه — سامانه ابری ثبت و مدیریت املاک برای مشاوران و آژانس‌های املاک. CRM، حسابداری، فایلینگ، اپ اندروید و ویندوز.',
            'canonical' => $this->baseUrl().'/',
            'type' => 'website',
            'body' => $this->homeBody(),
            'jsonLd' => [
                '@context' => 'https://schema.org',
                '@type' => 'SoftwareApplication',
                'name' => 'پوشه',
                'applicationCategory' => 'BusinessApplication',
                'operatingSystem' => 'Web, Android, Windows',
                'description' => 'سامانه مدیریت املاک برای مشاوران و دفاتر املاک در ایران',
                'url' => $this->baseUrl(),
            ],
        ]);
    }

    public function blogIndex(): View
    {
        $posts = BlogPost::published()
            ->orderByDesc('published_at')
            ->limit(50)
            ->get(['title', 'slug', 'excerpt', 'published_at']);

        $items = $posts->map(fn (BlogPost $p) => sprintf(
            '<li><a href="%s/blog/%s">%s</a>%s</li>',
            e($this->baseUrl()),
            e($p->slug),
            e($p->title),
            $p->excerpt ? ' — <span>'.e(Str::limit(strip_tags($p->excerpt), 120)).'</span>' : ''
        ))->implode("\n");

        return view('seo.page', [
            'title' => 'وبلاگ املاک و نرم‌افزار',
            'description' => 'مقالات تخصصی CRM املاک، فایلینگ، حسابداری دفتر املاک و دیجیتال مارکتینگ — پوشه',
            'canonical' => $this->baseUrl().'/blog',
            'type' => 'website',
            'body' => '<h1>وبلاگ پوشه</h1><p>راهنمای مشاوران و مدیران دفاتر املاک</p><ul>'.$items.'</ul>',
        ]);
    }

    public function blogCategory(string $category): View
    {
        $label = BlogController::CATEGORIES[$category] ?? $category;
        $posts = BlogPost::published()
            ->where('category_slug', $category)
            ->orderByDesc('published_at')
            ->limit(40)
            ->get(['title', 'slug', 'excerpt']);

        $items = $posts->map(fn (BlogPost $p) => sprintf(
            '<li><a href="%s/blog/%s">%s</a></li>',
            e($this->baseUrl()),
            e($p->slug),
            e($p->title),
        ))->implode("\n");

        return view('seo.page', [
            'title' => $label,
            'description' => "مقالات {$label} — وبلاگ پوشه",
            'canonical' => $this->baseUrl().'/blog/category/'.$category,
            'type' => 'website',
            'body' => '<h1>'.e($label).'</h1><ul>'.$items.'</ul>',
        ]);
    }

    public function blogPost(string $slug): View|Response
    {
        $post = BlogPost::published()->where('slug', $slug)->firstOrFail();
        $content = $post->content ?? '';
        $plain = Str::limit(strip_tags($content), 300);
        $cover = $post->cover_image ? url('storage/'.$post->cover_image) : null;

        return view('seo.page', [
            'title' => $post->meta_title ?: $post->title,
            'description' => $post->meta_description ?: $plain,
            'canonical' => $this->baseUrl().'/blog/'.$post->slug,
            'type' => 'article',
            'image' => $cover,
            'body' => '<article><h1>'.e($post->title).'</h1>'.$content.'</article>',
            'jsonLd' => [
                '@context' => 'https://schema.org',
                '@type' => 'Article',
                'headline' => $post->title,
                'description' => $post->meta_description ?: $plain,
                'datePublished' => $post->published_at?->toIso8601String(),
                'dateModified' => ($post->updated_at ?? $post->published_at)?->toIso8601String(),
                'author' => ['@type' => 'Organization', 'name' => 'پوشه'],
                'publisher' => [
                    '@type' => 'Organization',
                    'name' => 'پوشه',
                    'logo' => ['@type' => 'ImageObject', 'url' => $this->baseUrl().'/favicon.svg'],
                ],
                'mainEntityOfPage' => $this->baseUrl().'/blog/'.$post->slug,
            ],
        ]);
    }

    private function homeBody(): string
    {
        return <<<'HTML'
<h1>پوشه — سامانه مدیریت املاک</h1>
<p>ثبت ملک، CRM، حسابداری، مدیریت تیم، ربات تلگرام و واتساپ، اپ اندروید و ویندوز.</p>
<ul>
  <li><a href="/register">ثبت‌نام رایگان — ۳ روز آزمایشی</a></li>
  <li><a href="/blog">وبلاگ و آموزش املاک</a></li>
  <li><a href="/download">دانلود اپ اندروید و ویندوز</a></li>
  <li><a href="/consultants">دایرکتوری مشاوران</a></li>
</ul>
HTML;
    }

    private function baseUrl(): string
    {
        return rtrim(config('app.frontend_url', config('app.url')), '/');
    }
}
