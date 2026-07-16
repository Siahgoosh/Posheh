<?php

namespace App\Http\Controllers\Api\Blog;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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

    public function categories(): JsonResponse
    {
        $counts = BlogPost::published()
            ->selectRaw('category_slug, count(*) as total')
            ->whereNotNull('category_slug')
            ->groupBy('category_slug')
            ->pluck('total', 'category_slug');

        $data = collect(self::CATEGORIES)->map(fn ($label, $slug) => [
            'slug' => $slug,
            'label' => $label,
            'count' => (int) ($counts[$slug] ?? 0),
            'url' => '/blog/category/'.$slug,
        ])->values();

        return response()->json(['data' => $data]);
    }

    public function index(Request $request): JsonResponse
    {
        $posts = BlogPost::published()
            ->when($request->input('category'), fn ($q, $cat) => $q->where('category_slug', $cat))
            ->when($request->input('pillar'), fn ($q, $p) => $q->where('pillar_slug', $p))
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

    public function show(string $slug): JsonResponse
    {
        $post = BlogPost::published()->where('slug', $slug)->firstOrFail();
        $post->increment('views');

        $related = [];
        if ($post->related_slugs) {
            $related = BlogPost::published()
                ->whereIn('slug', $post->related_slugs)
                ->get()
                ->map(fn (BlogPost $p) => $this->listItem($p))
                ->values()
                ->all();
        }

        return response()->json([
            'data' => [
                ...$this->detailItem($post),
                'related' => $related,
            ],
        ]);
    }

    public function sitemap(): JsonResponse
    {
        $posts = BlogPost::published()
            ->orderByDesc('published_at')
            ->get(['slug', 'updated_at', 'published_at', 'category_slug']);

        $static = [
            ['path' => '/', 'priority' => 1.0],
            ['path' => '/blog', 'priority' => 0.9],
            ['path' => '/register', 'priority' => 0.9],
            ['path' => '/download', 'priority' => 0.8],
            ['path' => '/login', 'priority' => 0.5],
        ];

        foreach (self::CATEGORIES as $slug => $label) {
            $static[] = ['path' => '/blog/category/'.$slug, 'priority' => 0.75];
        }

        return response()->json([
            'static' => $static,
            'posts' => $posts->map(fn (BlogPost $post) => [
                'path' => '/blog/'.$post->slug,
                'slug' => $post->slug,
                'updated_at' => ($post->updated_at ?? $post->published_at)?->toIso8601String(),
            ]),
        ]);
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
            'published_at' => $post->published_at?->toIso8601String(),
            'published_at_jalali' => $post->published_at
                ? Jalalian::fromDateTime($post->published_at)->format('Y/m/d')
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
            'keywords' => $post->keywords,
            'faq' => $post->faq ?? [],
            'cta_text' => $post->cta_text,
            'cta_url' => $post->cta_url,
            'views' => $post->views,
            'updated_at' => $post->updated_at?->toIso8601String(),
        ];
    }
}
