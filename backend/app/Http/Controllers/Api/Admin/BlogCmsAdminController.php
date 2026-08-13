<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlogAuthor;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\BlogRedirect;
use App\Models\BlogTag;
use App\Services\Blog\BlogCmsBootstrapService;
use App\Services\Blog\BlogRelatedArticlesService;
use App\Services\Blog\BlogSitemapService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class BlogCmsAdminController extends Controller
{
    public function __construct(
        private readonly BlogCmsBootstrapService $bootstrap,
        private readonly BlogSitemapService $sitemap,
        private readonly BlogRelatedArticlesService $related,
    ) {}

    public function bootstrap(): JsonResponse
    {
        $result = $this->bootstrap->ensureDefaults();
        $status = ($result['ok'] ?? false) ? 200 : 422;

        return response()->json(['data' => $result], $status);
    }

    public function dashboard(): JsonResponse
    {
        $base = BlogPost::query();

        $duplicateTitles = BlogPost::query()
            ->selectRaw('title, count(*) as total')
            ->groupBy('title')
            ->havingRaw('count(*) > 1')
            ->limit(20)
            ->get();

        $orphanish = 0;
        try {
            $orphanishQuery = BlogPost::published()
                ->where(function ($q) {
                    $q->whereNull('related_slugs')->orWhere('related_slugs', '[]');
                });
            if (Schema::hasTable('blog_post_tag')) {
                $orphanishQuery->whereDoesntHave('tags');
            }
            $orphanish = $orphanishQuery->count();
        } catch (\Throwable) {
            $orphanish = 0;
        }

        $total = (clone $base)->count();
        $published = (clone $base)->where('is_published', true)->count();
        $missingImage = (clone $base)->where(fn ($q) => $q->whereNull('cover_image')->orWhere('cover_image', ''))->count();
        $missingMeta = (clone $base)->where(fn ($q) => $q->whereNull('meta_description')->orWhere('meta_description', ''))->count();
        $redirects = Schema::hasTable('blog_redirects')
            ? BlogRedirect::where('is_active', true)->count()
            : 0;

        $technical = $total === 0 ? 0 : (int) round(100 * (1 - min(1, $missingMeta / max(1, $total))));
        $content = $total === 0 ? 0 : (int) round(100 * ($published / max(1, $total)));
        $indexability = $published === 0 ? 0 : (int) round(100 * (1 - min(1, $orphanish / max(1, $published))));
        $metadata = $total === 0 ? 0 : (int) round(100 * (1 - min(1, $missingImage / max(1, $total))));

        return response()->json([
            'data' => [
                'total' => $total,
                'published' => $published,
                'draft' => (clone $base)->where('is_published', false)->where('review_status', '!=', 'scheduled')->count(),
                'scheduled' => (clone $base)->where('review_status', 'scheduled')->count(),
                'needs_review' => (clone $base)->whereIn('review_status', ['in_review', 'seo_review', 'content_review'])->count(),
                'missing_image' => $missingImage,
                'missing_meta' => $missingMeta,
                'categories' => Schema::hasTable('blog_categories') ? BlogCategory::count() : 0,
                'tags' => Schema::hasTable('blog_tags') ? BlogTag::count() : 0,
                'authors' => Schema::hasTable('blog_authors') ? BlogAuthor::count() : 0,
                'redirects' => $redirects,
                'duplicate_titles' => $duplicateTitles,
                'low_internal_link_signal' => $orphanish,
                'freshness' => [
                    'over_6m' => BlogPost::published()->where(function ($q) {
                        $q->where('content_updated_at', '<', now()->subMonths(6))
                            ->orWhere(fn ($qq) => $qq->whereNull('content_updated_at')->where('updated_at', '<', now()->subMonths(6)));
                    })->count(),
                    'over_12m' => BlogPost::published()->where(function ($q) {
                        $q->where('content_updated_at', '<', now()->subMonths(12))
                            ->orWhere(fn ($qq) => $qq->whereNull('content_updated_at')->where('updated_at', '<', now()->subMonths(12)));
                    })->count(),
                ],
                'gsc' => [
                    'enabled' => (bool) config('blog.gsc.enabled'),
                    'status' => config('blog.gsc.enabled') && config('blog.gsc.credentials_json')
                        ? 'پیکربندی‌شده'
                        : 'آماده اتصال',
                    'message' => 'API کنسول جستجوی گوگل تا افزودن credentials معتبر در حالت آماده است. ارسال نقشه سایت ≠ تضمین ایندکس.',
                ],
                'seo_health' => [
                    'note' => 'امتیاز داخلی از داده واقعی مقالات — نمره گوگل نیست',
                    'technical' => $technical,
                    'content' => $content,
                    'indexability' => $indexability,
                    'internal_linking' => $indexability,
                    'metadata' => $metadata,
                ],
            ],
        ]);
    }

    public function categories(): JsonResponse
    {
        return response()->json(['data' => BlogCategory::orderBy('sort_order')->orderBy('name')->get()]);
    }

    public function storeCategory(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:120', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', 'unique:blog_categories,slug'],
            'description' => ['nullable', 'string'],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'featured_image' => ['nullable', 'string', 'max:500'],
            'parent_id' => ['nullable', 'integer', 'exists:blog_categories,id'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
            'is_indexable' => ['sometimes', 'boolean'],
        ]);
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name'], '-', 'fa') ?: 'cat-'.Str::random(6);
        }
        $cat = BlogCategory::create($data);
        $this->sitemap->invalidate();

        return response()->json(['data' => $cat], 201);
    }

    public function updateCategory(Request $request, int $id): JsonResponse
    {
        $cat = BlogCategory::findOrFail($id);
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:120'],
            'slug' => ['sometimes', 'string', 'max:120', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('blog_categories', 'slug')->ignore($cat->id)],
            'description' => ['nullable', 'string'],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'featured_image' => ['nullable', 'string', 'max:500'],
            'parent_id' => ['nullable', 'integer', 'exists:blog_categories,id'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
            'is_indexable' => ['sometimes', 'boolean'],
        ]);
        $cat->update($data);
        $this->sitemap->invalidate();

        return response()->json(['data' => $cat->fresh()]);
    }

    public function tags(): JsonResponse
    {
        return response()->json(['data' => BlogTag::orderBy('name')->get()]);
    }

    public function storeTag(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:120', 'unique:blog_tags,slug'],
            'description' => ['nullable', 'string'],
            'is_indexable' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name'], '-', 'fa') ?: 'tag-'.Str::random(6);
        }
        $tag = BlogTag::create($data);

        return response()->json(['data' => $tag], 201);
    }

    public function updateTag(Request $request, int $id): JsonResponse
    {
        $tag = BlogTag::findOrFail($id);
        $tag->update($request->validate([
            'name' => ['sometimes', 'string', 'max:120'],
            'slug' => ['sometimes', 'string', 'max:120', Rule::unique('blog_tags', 'slug')->ignore($tag->id)],
            'description' => ['nullable', 'string'],
            'is_indexable' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
        ]));
        $this->sitemap->invalidate();

        return response()->json(['data' => $tag->fresh()]);
    }

    public function mergeTags(Request $request): JsonResponse
    {
        $data = $request->validate([
            'source_id' => ['required', 'integer', 'exists:blog_tags,id'],
            'target_id' => ['required', 'integer', 'exists:blog_tags,id', 'different:source_id'],
        ]);
        $source = BlogTag::findOrFail($data['source_id']);
        $target = BlogTag::findOrFail($data['target_id']);
        foreach ($source->posts()->pluck('blog_posts.id') as $postId) {
            $target->posts()->syncWithoutDetaching([$postId]);
        }
        $source->posts()->detach();
        $source->delete();

        return response()->json(['message' => 'برچسب‌ها ادغام شدند.', 'data' => $target->fresh()]);
    }

    public function authors(): JsonResponse
    {
        return response()->json(['data' => BlogAuthor::orderBy('name')->get()]);
    }

    public function storeAuthor(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:120', 'unique:blog_authors,slug'],
            'bio' => ['nullable', 'string'],
            'avatar' => ['nullable', 'string', 'max:500'],
            'role' => ['nullable', 'string', 'max:120'],
            'social_links' => ['nullable', 'array'],
            'is_indexable' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name'], '-', 'fa') ?: 'author-'.Str::random(6);
        }
        $author = BlogAuthor::create($data);

        return response()->json(['data' => $author], 201);
    }

    public function updateAuthor(Request $request, int $id): JsonResponse
    {
        $author = BlogAuthor::findOrFail($id);
        $author->update($request->validate([
            'name' => ['sometimes', 'string', 'max:120'],
            'slug' => ['sometimes', 'string', 'max:120', Rule::unique('blog_authors', 'slug')->ignore($author->id)],
            'bio' => ['nullable', 'string'],
            'avatar' => ['nullable', 'string', 'max:500'],
            'role' => ['nullable', 'string', 'max:120'],
            'social_links' => ['nullable', 'array'],
            'is_indexable' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
        ]));

        return response()->json(['data' => $author->fresh()]);
    }

    public function redirects(): JsonResponse
    {
        return response()->json(['data' => BlogRedirect::orderByDesc('id')->limit(200)->get()]);
    }

    public function storeRedirect(Request $request): JsonResponse
    {
        $data = $request->validate([
            'from_path' => ['required', 'string', 'max:500', 'unique:blog_redirects,from_path'],
            'to_path' => ['required', 'string', 'max:500'],
            'status_code' => ['nullable', 'integer', 'in:301,302,307,308'],
            'note' => ['nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
        $data['from_path'] = '/'.ltrim($data['from_path'], '/');
        $data['to_path'] = '/'.ltrim($data['to_path'], '/');
        $data['status_code'] = $data['status_code'] ?? 301;

        if ($data['from_path'] === $data['to_path']) {
            return response()->json(['message' => 'Redirect loop: source equals destination.'], 422);
        }

        // Simple chain/loop detection (one hop)
        $destIsSource = BlogRedirect::where('is_active', true)->where('from_path', $data['to_path'])->exists();
        $sourceIsDest = BlogRedirect::where('is_active', true)->where('to_path', $data['from_path'])->exists();
        if ($destIsSource || $sourceIsDest) {
            return response()->json([
                'message' => 'Redirect chain/loop risk detected. Resolve existing redirects first.',
                'code' => 'redirect_chain_or_loop',
            ], 422);
        }

        $redirect = BlogRedirect::create($data);

        return response()->json(['data' => $redirect], 201);
    }

    public function deleteRedirect(int $id): JsonResponse
    {
        BlogRedirect::findOrFail($id)->delete();

        return response()->json(['message' => 'Redirect حذف شد.']);
    }

    public function bulk(Request $request): JsonResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:blog_posts,id'],
            'action' => ['required', 'string', 'in:set_category,set_status,set_author,set_tags,publish,unpublish,archive,trash,noindex,index,export'],
            'category_slug' => ['nullable', 'string'],
            'review_status' => ['nullable', 'string'],
            'author_name' => ['nullable', 'string', 'max:100'],
            'blog_author_id' => ['nullable', 'integer', 'exists:blog_authors,id'],
            'tag_ids' => ['nullable', 'array'],
            'tag_ids.*' => ['integer', 'exists:blog_tags,id'],
            'confirm_destructive' => ['sometimes', 'boolean'],
            'confirm_affected' => ['sometimes', 'boolean'],
        ]);

        $posts = BlogPost::whereIn('id', $data['ids'])->get();

        $destructive = in_array($data['action'], ['publish', 'trash', 'noindex', 'archive'], true);
        if ($destructive && ! $request->boolean('confirm_destructive')) {
            return response()->json([
                'message' => 'عملیات خطرناک — confirm_destructive=1 لازم است.',
                'affected' => $posts->map(fn (BlogPost $p) => [
                    'id' => $p->id,
                    'title' => $p->title,
                    'slug' => $p->slug,
                    'is_published' => $p->is_published,
                    'review_status' => $p->review_status,
                ]),
                'count' => $posts->count(),
            ], 422);
        }

        if (! $request->boolean('confirm_affected') && $data['action'] !== 'export') {
            return response()->json([
                'message' => 'قبل از اجرا Affected Articles را تأیید کنید (confirm_affected=1).',
                'affected' => $posts->map(fn (BlogPost $p) => [
                    'id' => $p->id,
                    'title' => $p->title,
                    'slug' => $p->slug,
                ]),
                'count' => $posts->count(),
            ], 422);
        }

        if ($data['action'] === 'export') {
            return response()->json([
                'data' => $posts->map(fn (BlogPost $p) => $p->only([
                    'id', 'slug', 'title', 'category_slug', 'meta_title', 'meta_description',
                    'is_published', 'review_status', 'focus_keyword', 'published_at',
                ])),
            ]);
        }

        foreach ($posts as $post) {
            match ($data['action']) {
                'set_category' => ! empty($data['category_slug']) ? $post->update([
                    'category_slug' => $data['category_slug'],
                    'category_label' => BlogCategory::where('slug', $data['category_slug'])->value('name')
                        ?? (\App\Http\Controllers\Api\Blog\BlogController::CATEGORIES[$data['category_slug']] ?? $data['category_slug']),
                ]) : null,
                'set_status' => ! empty($data['review_status']) ? $post->update(['review_status' => $data['review_status']]) : null,
                'set_author' => $post->update(array_filter([
                    'author_name' => $data['author_name'] ?? null,
                    'blog_author_id' => $data['blog_author_id'] ?? null,
                ], fn ($v) => $v !== null)),
                'set_tags' => is_array($data['tag_ids'] ?? null) ? $post->tags()->sync($data['tag_ids']) : null,
                'publish' => $post->update(['is_published' => true, 'review_status' => BlogPost::REVIEW_PUBLISHED, 'published_at' => $post->published_at ?? now()]),
                'unpublish' => $post->update(['is_published' => false, 'review_status' => BlogPost::REVIEW_UNPUBLISHED]),
                'archive' => $post->update(['is_published' => false, 'review_status' => BlogPost::REVIEW_ARCHIVED, 'robots_directive' => 'noindex,follow']),
                'trash' => $post->update(['is_published' => false, 'review_status' => BlogPost::REVIEW_TRASH, 'robots_directive' => 'noindex,nofollow']),
                'noindex' => $post->update(['robots_directive' => 'noindex,follow']),
                'index' => $post->update(['robots_directive' => 'index,follow']),
                default => null,
            };
        }

        $this->sitemap->invalidate();

        return response()->json(['message' => 'Bulk action applied.', 'count' => $posts->count()]);
    }

    public function calendar(Request $request): JsonResponse
    {
        $from = $request->input('from', now()->subMonth()->toDateString());
        $to = $request->input('to', now()->addMonth()->toDateString());

        $posts = BlogPost::query()
            ->where(function ($q) use ($from, $to) {
                $q->whereBetween('scheduled_at', [$from, $to])
                    ->orWhereBetween('published_at', [$from, $to])
                    ->orWhere(function ($qq) use ($from, $to) {
                        $qq->where('is_published', false)
                            ->whereBetween('updated_at', [$from, $to]);
                    });
            })
            ->orderBy('scheduled_at')
            ->orderBy('published_at')
            ->limit(300)
            ->get(['id', 'title', 'slug', 'review_status', 'is_published', 'scheduled_at', 'published_at', 'category_slug']);

        return response()->json([
            'data' => $posts->map(fn (BlogPost $p) => [
                'id' => $p->id,
                'title' => $p->title,
                'slug' => $p->slug,
                'review_status' => $p->review_status,
                'is_published' => $p->is_published,
                'scheduled_at' => $p->scheduled_at?->toIso8601String(),
                'published_at' => $p->published_at?->toIso8601String(),
                'category_slug' => $p->category_slug,
                'kind' => $p->scheduled_at && ! $p->is_published
                    ? 'scheduled'
                    : ($p->is_published ? 'published' : 'draft'),
            ]),
            'range' => compact('from', 'to'),
        ]);
    }

    public function media(Request $request): JsonResponse
    {
        $disk = \Illuminate\Support\Facades\Storage::disk('public');
        $files = collect($disk->files('blog'))
            ->filter(fn ($p) => preg_match('/\.(jpe?g|png|webp|gif)$/i', $p))
            ->sortDesc()
            ->values();

        $q = trim((string) $request->input('q', ''));
        if ($q !== '') {
            $files = $files->filter(fn ($p) => str_contains(mb_strtolower($p), mb_strtolower($q)))->values();
        }

        $page = max(1, (int) $request->input('page', 1));
        $perPage = min(50, max(10, (int) $request->input('per_page', 24)));
        $slice = $files->slice(($page - 1) * $perPage, $perPage)->values();

        $items = $slice->map(function (string $path) use ($disk) {
            $url = $disk->url($path);
            $usage = BlogPost::query()
                ->where('cover_image', 'like', '%'.$path.'%')
                ->orWhere('content', 'like', '%'.$path.'%')
                ->orWhere('og_image', 'like', '%'.$path.'%')
                ->count();

            return [
                'path' => $path,
                'url' => $url,
                'size' => $disk->exists($path) ? $disk->size($path) : null,
                'last_modified' => $disk->exists($path) ? $disk->lastModified($path) : null,
                'usage_count' => $usage,
                'unused' => $usage === 0,
            ];
        });

        return response()->json([
            'data' => $items,
            'meta' => [
                'total' => $files->count(),
                'page' => $page,
                'per_page' => $perPage,
                'unused' => $files->filter(function ($path) {
                    return BlogPost::query()
                        ->where('cover_image', 'like', '%'.$path.'%')
                        ->orWhere('content', 'like', '%'.$path.'%')
                        ->orWhere('og_image', 'like', '%'.$path.'%')
                        ->count() === 0;
                })->count(),
            ],
        ]);
    }

    public function deleteMedia(Request $request): JsonResponse
    {
        $data = $request->validate([
            'path' => ['required', 'string', 'max:500'],
            'confirm' => ['required', 'boolean'],
        ]);
        if (! $data['confirm']) {
            return response()->json(['message' => 'confirm=true لازم است.'], 422);
        }
        $path = ltrim($data['path'], '/');
        if (! str_starts_with($path, 'blog/')) {
            return response()->json(['message' => 'Path outside blog media isolation.'], 422);
        }
        $usage = BlogPost::query()
            ->where('cover_image', 'like', '%'.$path.'%')
            ->orWhere('content', 'like', '%'.$path.'%')
            ->orWhere('og_image', 'like', '%'.$path.'%')
            ->limit(5)
            ->get(['id', 'slug', 'title']);
        if ($usage->isNotEmpty() && ! $request->boolean('force')) {
            return response()->json([
                'message' => 'Usage Check failed — فایل در حال استفاده است.',
                'usage' => $usage,
            ], 422);
        }
        \Illuminate\Support\Facades\Storage::disk('public')->delete($path);

        return response()->json(['message' => 'حذف شد.']);
    }

    public function suggestLinks(int $id): JsonResponse
    {
        $post = BlogPost::findOrFail($id);
        $items = $this->related->suggest($post, 10)->map(fn ($row) => [
            'slug' => $row['post']->slug ?? ($row['slug'] ?? null),
            'title' => $row['post']->title ?? ($row['title'] ?? null),
            'score' => $row['score'] ?? null,
            'reason' => $row['reason'] ?? 'semantic/topic',
            'breakdown' => $row['breakdown'] ?? null,
            'suggested_anchor' => $row['post']->title ?? ($row['title'] ?? 'مقاله مرتبط'),
        ]);

        return response()->json(['data' => $items]);
    }

    public function exportCsv(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $filename = 'blog-export-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['id', 'slug', 'title', 'category', 'status', 'published', 'meta_title', 'focus_keyword']);
            BlogPost::orderBy('id')->chunk(100, function ($chunk) use ($out) {
                foreach ($chunk as $post) {
                    fputcsv($out, [
                        $post->id,
                        $post->slug,
                        $post->title,
                        $post->category_slug,
                        $post->review_status,
                        $post->is_published ? 1 : 0,
                        $post->meta_title,
                        $post->focus_keyword,
                    ]);
                }
            });
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
