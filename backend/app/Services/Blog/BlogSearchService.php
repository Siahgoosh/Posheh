<?php

namespace App\Services\Blog;

use App\Models\BlogPost;
use Illuminate\Support\Collection;

class BlogSearchService
{
    public function __construct(
        private readonly PersianTextNormalizer $normalizer,
    ) {}

    /** @return array{data: list<array<string, mixed>>, meta: array<string, int|string>} */
    public function search(string $query, int $page = 1, int $perPage = 12): array
    {
        $query = trim($query);
        $min = (int) config('blog.search.min_query_length', 2);
        $perPage = min(50, max(1, $perPage ?: (int) config('blog.search.per_page', 12)));

        if (mb_strlen($query) < $min) {
            return [
                'data' => [],
                'meta' => ['total' => 0, 'current_page' => 1, 'last_page' => 1, 'query' => $query],
            ];
        }

        $normalized = $this->normalizer->normalize($query);
        $candidates = BlogPost::published()
            ->orderByDesc('published_at')
            ->limit(500)
            ->get(['id', 'slug', 'title', 'excerpt', 'content', 'category_slug', 'category_label', 'keywords', 'focus_keyword', 'cover_image', 'author_name', 'reading_time', 'published_at', 'views']);

        $scored = $candidates->map(function (BlogPost $post) use ($normalized) {
            $score = 0;
            $title = $this->normalizer->normalize((string) $post->title);
            $excerpt = $this->normalizer->normalize((string) $post->excerpt);
            $keywords = $this->normalizer->normalize((string) ($post->focus_keyword ?: $post->keywords));
            $category = $this->normalizer->normalize((string) $post->category_label);
            $content = $this->normalizer->normalize(mb_substr(strip_tags((string) $post->content), 0, 4000));

            if (str_contains($title, $normalized)) {
                $score += 50;
            }
            if (str_contains($excerpt, $normalized)) {
                $score += 20;
            }
            if (str_contains($keywords, $normalized)) {
                $score += 15;
            }
            if (str_contains($category, $normalized)) {
                $score += 10;
            }
            if (str_contains($content, $normalized)) {
                $score += 8;
            }

            // token AND soft match
            foreach (preg_split('/\s+/u', $normalized) ?: [] as $token) {
                if (mb_strlen($token) < 2) {
                    continue;
                }
                if (str_contains($title, $token)) {
                    $score += 6;
                } elseif (str_contains($content, $token)) {
                    $score += 2;
                }
            }

            return ['post' => $post, 'score' => $score];
        })->filter(fn ($row) => $row['score'] > 0)
            ->sortByDesc('score')
            ->values();

        $total = $scored->count();
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($page, $lastPage));
        $slice = $scored->forPage($page, $perPage)->values();

        return [
            'data' => $slice->map(fn ($row) => [
                'slug' => $row['post']->slug,
                'title' => $row['post']->title,
                'excerpt' => $row['post']->excerpt,
                'category_slug' => $row['post']->category_slug,
                'category_label' => $row['post']->category_label,
                'cover_image' => $row['post']->cover_image,
                'author_name' => $row['post']->author_name,
                'reading_time' => $row['post']->reading_time,
                'published_at' => $row['post']->published_at?->toIso8601String(),
                'score' => $row['score'],
            ])->all(),
            'meta' => [
                'total' => $total,
                'current_page' => $page,
                'last_page' => $lastPage,
                'query' => $query,
            ],
        ];
    }
}
