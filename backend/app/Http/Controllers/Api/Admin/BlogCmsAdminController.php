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
        $this->bootstrap->ensureDefaults();

        return response()->json(['message' => 'OK']);
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

        $orphanish = BlogPost::published()
            ->where(function ($q) {
                $q->whereNull('related_slugs')->orWhere('related_slugs', '[]');
            })
            ->whereDoesntHave('tags')
            ->count();

        return response()->json([
            'data' => [
                'total' => (clone $base)->count(),
                'published' => (clone $base)->where('is_published', true)->count(),
                'draft' => (clone $base)->where('is_published', false)->where('review_status', '!=', 'scheduled')->count(),
                'scheduled' => (clone $base)->where('review_status', 'scheduled')->count(),
                'needs_review' => (clone $base)->whereIn('review_status', ['in_review', 'seo_review', 'content_review'])->count(),
                'missing_image' => (clone $base)->where(fn ($q) => $q->whereNull('cover_image')->orWhere('cover_image', ''))->count(),
                'missing_meta' => (clone $base)->where(fn ($q) => $q->whereNull('meta_description')->orWhere('meta_description', ''))->count(),
                'categories' => BlogCategory::count(),
                'tags' => BlogTag::count(),
                'authors' => BlogAuthor::count(),
                'redirects' => BlogRedirect::where('is_active', true)->count(),
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
                        ? 'configured'
                        : 'placeholder',
                    'message' => 'Google Search Console API در حالت placeholder است تا credentials معتبر اضافه شود. Sitemap submission ≠ indexing guarantee.',
                ],
                'seo_health' => [
                    'note' => 'امتیاز داخلی مدیریت — نمره گوگل نیست',
                    'technical' => 75,
                    'content' => 55,
                    'indexability' => 80,
                    'internal_linking' => 60,
                    'metadata' => 70,
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
        $data['status_code'] = $data['status_code'] ?? 301;
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
            'action' => ['required', 'string', 'in:set_category,set_status,export'],
            'category_slug' => ['nullable', 'string'],
            'review_status' => ['nullable', 'string'],
            'confirm_destructive' => ['sometimes', 'boolean'],
        ]);

        $posts = BlogPost::whereIn('id', $data['ids'])->get();

        if ($data['action'] === 'export') {
            return response()->json([
                'data' => $posts->map(fn (BlogPost $p) => $p->only([
                    'id', 'slug', 'title', 'category_slug', 'meta_title', 'meta_description',
                    'is_published', 'review_status', 'focus_keyword', 'published_at',
                ])),
            ]);
        }

        foreach ($posts as $post) {
            if ($data['action'] === 'set_category' && ! empty($data['category_slug'])) {
                $label = BlogCategory::where('slug', $data['category_slug'])->value('name')
                    ?? (\App\Http\Controllers\Api\Blog\BlogController::CATEGORIES[$data['category_slug']] ?? $data['category_slug']);
                $post->update([
                    'category_slug' => $data['category_slug'],
                    'category_label' => $label,
                ]);
            }
            if ($data['action'] === 'set_status' && ! empty($data['review_status'])) {
                $post->update(['review_status' => $data['review_status']]);
            }
        }

        return response()->json(['message' => 'Bulk action applied.', 'count' => $posts->count()]);
    }

    public function suggestLinks(int $id): JsonResponse
    {
        $post = BlogPost::findOrFail($id);
        $items = $this->related->suggest($post, 10)->map(fn ($row) => [
            'slug' => $row['post']->slug,
            'title' => $row['post']->title,
            'score' => $row['score'],
            'breakdown' => $row['breakdown'],
            'suggested_anchor' => $row['post']->title,
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
