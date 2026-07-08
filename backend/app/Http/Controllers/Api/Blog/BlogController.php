<?php

namespace App\Http\Controllers\Api\Blog;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BlogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $posts = BlogPost::published()
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

        return response()->json(['data' => $this->detailItem($post)]);
    }

    public function sitemap(): JsonResponse
    {
        $posts = BlogPost::published()
            ->orderByDesc('published_at')
            ->get(['slug', 'updated_at', 'published_at']);

        return response()->json([
            'data' => $posts->map(fn (BlogPost $post) => [
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
            'author_name' => $post->author_name,
            'reading_time' => $post->reading_time,
            'published_at' => $post->published_at?->toIso8601String(),
            'published_at_jalali' => $post->published_at?->format('Y-m-d'),
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
            'views' => $post->views,
            'updated_at' => $post->updated_at?->toIso8601String(),
        ];
    }
}
