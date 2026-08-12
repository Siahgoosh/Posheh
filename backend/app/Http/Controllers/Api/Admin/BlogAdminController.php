<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use App\Services\Blog\BlogContentQualityScorer;
use App\Services\Blog\BlogQualityGate;
use App\Services\Blog\BlogSeoAnalyzer;
use App\Services\Blog\BlogVersioningService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class BlogAdminController extends Controller
{
    public function __construct(
        private readonly BlogSeoAnalyzer $seoAnalyzer,
        private readonly BlogContentQualityScorer $qualityScorer,
        private readonly BlogQualityGate $qualityGate,
        private readonly BlogVersioningService $versioning,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $posts = BlogPost::query()
            ->when($request->input('review_status'), fn ($q, $s) => $q->where('review_status', $s))
            ->when($request->boolean('rebuild_locked'), fn ($q) => $q->where('rebuild_locked', true))
            ->orderByDesc('updated_at')
            ->paginate(min((int) $request->input('per_page', 20), 50));

        return response()->json([
            'data' => $posts->getCollection()->map(fn (BlogPost $post) => $this->adminItem($post)),
            'meta' => [
                'current_page' => $posts->currentPage(),
                'last_page' => $posts->lastPage(),
                'total' => $posts->total(),
            ],
        ]);
    }

    public function health(): JsonResponse
    {
        $base = BlogPost::query();

        return response()->json([
            'data' => [
                'total' => (clone $base)->count(),
                'published' => (clone $base)->where('is_published', true)->count(),
                'draft' => (clone $base)->where('is_published', false)->count(),
                'needs_review' => (clone $base)->whereIn('review_status', ['seo_review', 'content_review'])->count(),
                'rebuild_locked' => (clone $base)->where('rebuild_locked', true)->count(),
                'missing_image' => (clone $base)->where(fn ($q) => $q->whereNull('cover_image')->orWhere('cover_image', ''))->count(),
                'missing_meta' => (clone $base)->where(fn ($q) => $q->whereNull('meta_description')->orWhere('meta_description', ''))->count(),
                'missing_faq' => (clone $base)->where(fn ($q) => $q->whereNull('faq')->orWhere('faq', '[]'))->count(),
                'by_review_status' => BlogPost::query()
                    ->selectRaw('review_status, count(*) as total')
                    ->groupBy('review_status')
                    ->pluck('total', 'review_status'),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $post = BlogPost::findOrFail($id);

        return response()->json([
            'data' => $this->adminItem($post, includeContent: true),
            'seo' => $this->seoAnalyzer->analyze($post->toArray()),
            'quality' => $this->qualityScorer->score($post->toArray()),
            'gate' => $this->qualityGate->evaluate($post->toArray(), forPublish: false),
            'versions' => $post->versions()->orderByDesc('version')->limit(20)->get(['id', 'version', 'note', 'created_by', 'created_at']),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);
        $data['quality_scores'] = ['after' => $this->qualityScorer->score($data)];
        $post = BlogPost::create($data);
        $this->versioning->snapshot($post, 'create', $request->user()?->email);

        return response()->json([
            'data' => $this->adminItem($post, includeContent: true),
            'seo' => $this->seoAnalyzer->analyze($post->toArray()),
            'quality' => $this->qualityScorer->score($post->toArray()),
            'gate' => $this->qualityGate->evaluate($post->toArray(), forPublish: (bool) $post->is_published),
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $post = BlogPost::findOrFail($id);
        $data = $this->validated($request, $post);

        if (! empty($data['is_published'])) {
            $gate = $this->qualityGate->evaluate(array_merge($post->toArray(), $data), forPublish: true);
            if (! $gate['passed']) {
                return response()->json([
                    'message' => 'انتشار مسدود شد. موارد زیر را برطرف کنید.',
                    'gate' => $gate,
                ], 422);
            }
            $data['review_status'] = BlogPost::REVIEW_PUBLISHED;
            if (($data['robots_directive'] ?? null) === 'noindex,nofollow') {
                $data['robots_directive'] = 'index,follow';
            }
        }

        $this->versioning->snapshot($post, 'before-update', $request->user()?->email);
        $data['quality_scores'] = [
            'before' => $post->quality_scores['after'] ?? $this->qualityScorer->score($post->toArray()),
            'after' => $this->qualityScorer->score(array_merge($post->toArray(), $data)),
        ];
        $post->update($data);

        return response()->json([
            'data' => $this->adminItem($post->fresh(), includeContent: true),
            'seo' => $this->seoAnalyzer->analyze($post->fresh()->toArray()),
            'quality' => $this->qualityScorer->score($post->fresh()->toArray()),
            'gate' => $this->qualityGate->evaluate($post->fresh()->toArray(), forPublish: (bool) $post->fresh()->is_published),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        BlogPost::findOrFail($id)->delete();

        return response()->json(['message' => 'مقاله حذف شد.']);
    }

    public function categories(): JsonResponse
    {
        return response()->json([
            'data' => collect(\App\Http\Controllers\Api\Blog\BlogController::CATEGORIES)
                ->map(fn ($label, $slug) => ['slug' => $slug, 'label' => $label])
                ->values(),
        ]);
    }

    public function analyzeSeo(Request $request): JsonResponse
    {
        $request->validate([
            'title' => ['nullable', 'string'],
            'slug' => ['nullable', 'string'],
            'excerpt' => ['nullable', 'string'],
            'content' => ['nullable', 'string'],
            'meta_title' => ['nullable', 'string'],
            'meta_description' => ['nullable', 'string'],
            'keywords' => ['nullable', 'string'],
            'cover_image' => ['nullable', 'string'],
            'category_slug' => ['nullable', 'string'],
            'pillar_slug' => ['nullable', 'string'],
            'faq' => ['nullable', 'array'],
            'related_slugs' => ['nullable', 'array'],
        ]);

        return response()->json([
            'data' => $this->seoAnalyzer->analyze($request->all()),
        ]);
    }

    public function uploadImage(Request $request): JsonResponse
    {
        $request->validate([
            'image' => ['required', 'image', 'max:5120'],
            'alt' => ['nullable', 'string', 'max:200'],
        ]);

        return response()->json([
            'data' => $this->storeUploadedImage($request->file('image'), $request->input('alt', '')),
        ], 201);
    }

    public function uploadCover(Request $request): JsonResponse
    {
        $request->validate([
            'image' => ['required', 'image', 'max:8192'],
        ]);

        $data = $this->storeUploadedImage($request->file('image'), 'cover');

        return response()->json([
            'data' => [
                'url' => $data['url'],
                'path' => $data['path'],
            ],
        ], 201);
    }

    /** @return array{url: string, path: string, html: string} */
    private function storeUploadedImage($file, string $alt): array
    {
        $path = $file->store('blog', 'public');
        $url = Storage::disk('public')->url($path);

        return [
            'url' => $url,
            'path' => $path,
            'html' => $alt !== '' && $alt !== 'cover'
                ? '<img src="'.e($url).'" alt="'.e($alt).'" loading="lazy" />'
                : '<img src="'.e($url).'" alt="" loading="lazy" />',
        ];
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, ?BlogPost $existing = null): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('blog_posts', 'slug')->ignore($existing?->id),
            ],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'content' => ['required', 'string'],
            'cover_image' => ['nullable', 'string', 'max:500'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'keywords' => ['nullable', 'string', 'max:255'],
            'author_name' => ['nullable', 'string', 'max:100'],
            'reading_time' => ['nullable', 'integer', 'min:1', 'max:120'],
            'is_published' => ['sometimes', 'boolean'],
            'published_at' => ['nullable', 'date'],
            'category_slug' => ['nullable', 'string', 'max:50'],
            'category_label' => ['nullable', 'string', 'max:100'],
            'pillar_slug' => ['nullable', 'string', 'max:100'],
            'faq' => ['nullable', 'array'],
            'faq.*.question' => ['required_with:faq', 'string', 'max:300'],
            'faq.*.answer' => ['required_with:faq', 'string', 'max:2000'],
            'related_slugs' => ['nullable', 'array'],
            'related_slugs.*' => ['string', 'max:255'],
            'cta_text' => ['nullable', 'string', 'max:200'],
            'cta_url' => ['nullable', 'string', 'max:500'],
            'focus_keyword' => ['nullable', 'string', 'max:120'],
            'secondary_keywords' => ['nullable', 'array'],
            'secondary_keywords.*' => ['string', 'max:120'],
            'search_intent' => ['nullable', 'string', 'max:40'],
            'business_intent' => ['nullable', 'string', 'max:40'],
            'review_status' => ['nullable', 'string', 'max:40'],
            'rebuild_locked' => ['sometimes', 'boolean'],
            'canonical_url' => ['nullable', 'string', 'max:500'],
            'robots_directive' => ['nullable', 'string', 'max:60'],
            'image_prompt' => ['nullable', 'string', 'max:2000'],
            'scheduled_at' => ['nullable', 'date'],
            'content_brief' => ['nullable', 'array'],
        ]);

        if (! empty($data['category_slug']) && empty($data['category_label'])) {
            $data['category_label'] = \App\Http\Controllers\Api\Blog\BlogController::CATEGORIES[$data['category_slug']] ?? null;
        }

        if (empty($data['slug'])) {
            $data['slug'] = BlogPost::makeSlug($data['title']);
        }

        if (empty($data['reading_time'])) {
            $plain = strip_tags($data['content']);
            $data['reading_time'] = max(1, (int) ceil(mb_strlen($plain) / 800));
        }

        if (($data['is_published'] ?? false) && empty($data['published_at'])) {
            $data['published_at'] = now();
        }

        if (array_key_exists('is_published', $data) && ! ($data['is_published'] ?? false)) {
            $data['published_at'] = null;
            $data['review_status'] = $data['review_status'] ?? BlogPost::REVIEW_DRAFT;
        }

        return $data;
    }

    private function adminItem(BlogPost $post, bool $includeContent = false): array
    {
        $item = [
            'id' => $post->id,
            'slug' => $post->slug,
            'category_slug' => $post->category_slug,
            'category_label' => $post->category_label,
            'pillar_slug' => $post->pillar_slug,
            'title' => $post->title,
            'excerpt' => $post->excerpt,
            'cover_image' => $post->cover_image,
            'meta_title' => $post->meta_title,
            'meta_description' => $post->meta_description,
            'canonical_url' => $post->canonical_url,
            'robots_directive' => $post->robots_directive,
            'keywords' => $post->keywords,
            'focus_keyword' => $post->focus_keyword,
            'secondary_keywords' => $post->secondary_keywords ?? [],
            'search_intent' => $post->search_intent,
            'business_intent' => $post->business_intent,
            'faq' => $post->faq ?? [],
            'related_slugs' => $post->related_slugs ?? [],
            'cta_text' => $post->cta_text,
            'cta_url' => $post->cta_url,
            'author_name' => $post->author_name,
            'reading_time' => $post->reading_time,
            'views' => $post->views,
            'is_published' => $post->is_published,
            'review_status' => $post->review_status,
            'rebuild_locked' => (bool) $post->rebuild_locked,
            'quality_scores' => $post->quality_scores,
            'content_brief' => $post->content_brief,
            'image_prompt' => $post->image_prompt,
            'scheduled_at' => $post->scheduled_at?->toIso8601String(),
            'published_at' => $post->published_at?->toIso8601String(),
            'updated_at' => $post->updated_at?->toIso8601String(),
        ];

        if ($includeContent) {
            $item['content'] = $post->content;
        }

        return $item;
    }
}
