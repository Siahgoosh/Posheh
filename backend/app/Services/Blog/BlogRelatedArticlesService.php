<?php

namespace App\Services\Blog;

use App\Models\BlogPost;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class BlogRelatedArticlesService
{
    public function __construct(
        private readonly PersianTextNormalizer $normalizer,
    ) {}

    /** @return Collection<int, array{post: BlogPost, score: int, breakdown: array<string, int>}> */
    public function suggest(BlogPost $post, int $limit = 6): Collection
    {
        $weights = config('blog.related_weights', []);
        $candidates = BlogPost::published()
            ->where('id', '!=', $post->id)
            ->orderByDesc('published_at')
            ->limit(200)
            ->get();

        $focus = $this->normalizer->normalize((string) ($post->focus_keyword ?: $post->keywords));
        $titleTokens = $this->tokens((string) $post->title);

        return $candidates->map(function (BlogPost $candidate) use ($post, $weights, $focus, $titleTokens) {
            $topic = 0;
            $candTitle = $this->normalizer->normalize((string) $candidate->title);
            foreach ($titleTokens as $token) {
                if (mb_strlen($token) >= 3 && str_contains($candTitle, $token)) {
                    $topic += 10;
                }
            }
            $topic = min(100, $topic);

            $semantic = 0;
            $candFocus = $this->normalizer->normalize((string) ($candidate->focus_keyword ?: $candidate->keywords));
            if ($focus !== '' && $candFocus !== '' && (str_contains($candFocus, $focus) || str_contains($focus, $candFocus))) {
                $semantic = 80;
            } elseif ($focus !== '' && str_contains($this->normalizer->normalize((string) $candidate->excerpt), $focus)) {
                $semantic = 40;
            }

            $category = ($post->category_slug && $post->category_slug === $candidate->category_slug) ? 100 : 0;
            $keywords = 0;
            if ($focus !== '' && str_contains($candFocus, explode(' ', $focus)[0] ?? '')) {
                $keywords = 70;
            }

            $days = max(1, Carbon::now()->diffInDays($candidate->published_at ?? now()));
            $freshness = (int) max(0, 100 - min(100, $days / 3));

            $score = (int) round(
                ($topic * ($weights['topic'] ?? 40)
                + $semantic * ($weights['semantic'] ?? 25)
                + $category * ($weights['category'] ?? 15)
                + $keywords * ($weights['keywords'] ?? 10)
                + $freshness * ($weights['freshness'] ?? 10)) / 100
            );

            return [
                'post' => $candidate,
                'score' => $score,
                'breakdown' => compact('topic', 'semantic', 'category', 'keywords', 'freshness'),
            ];
        })->sortByDesc('score')->take($limit)->values();
    }

    /** @return list<string> */
    private function tokens(string $text): array
    {
        $n = $this->normalizer->normalize($text);

        return array_values(array_filter(preg_split('/\s+/u', $n) ?: [], fn ($t) => mb_strlen($t) >= 3));
    }
}
