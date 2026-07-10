<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use App\Services\Blog\BlogSeoAnalyzer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class BlogAdminController extends Controller
{
    public function __construct(
        private readonly BlogSeoAnalyzer $seoAnalyzer,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $posts = BlogPost::query()
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

    public function show(int $id): JsonResponse
    {
        $post = BlogPost::findOrFail($id);

        return response()->json([
            'data' => $this->adminItem($post, includeContent: true),
            'seo' => $this->seoAnalyzer->analyze($post->toArray()),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);
        $post = BlogPost::create($data);

        return response()->json([
            'data' => $this->adminItem($post, includeContent: true),
            'seo' => $this->seoAnalyzer->analyze($post->toArray()),
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $post = BlogPost::findOrFail($id);
        $post->update($this->validated($request, $post));

        return response()->json([
            'data' => $this->adminItem($post->fresh(), includeContent: true),
            'seo' => $this->seoAnalyzer->analyze($post->fresh()->toArray()),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        BlogPost::findOrFail($id)->delete();

        return response()->json(['message' => 'مقاله حذف شد.']);
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

        $path = $request->file('image')->store('blog', 'public');
        $url = Storage::disk('public')->url($path);
        $alt = $request->input('alt', '');

        return response()->json([
            'data' => [
                'url' => $url,
                'path' => $path,
                'html' => $alt !== ''
                    ? '<img src="'.e($url).'" alt="'.e($alt).'" loading="lazy" />'
                    : '<img src="'.e($url).'" alt="" loading="lazy" />',
            ],
        ], 201);
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
        ]);

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

        if (! ($data['is_published'] ?? false)) {
            $data['published_at'] = null;
        }

        return $data;
    }

  private function adminItem(BlogPost $post, bool $includeContent = false): array
    {
        $item = [
            'id' => $post->id,
            'slug' => $post->slug,
            'title' => $post->title,
            'excerpt' => $post->excerpt,
            'cover_image' => $post->cover_image,
            'meta_title' => $post->meta_title,
            'meta_description' => $post->meta_description,
            'keywords' => $post->keywords,
            'author_name' => $post->author_name,
            'reading_time' => $post->reading_time,
            'views' => $post->views,
            'is_published' => $post->is_published,
            'published_at' => $post->published_at?->toIso8601String(),
            'updated_at' => $post->updated_at?->toIso8601String(),
        ];

        if ($includeContent) {
            $item['content'] = $post->content;
        }

        return $item;
    }
}
