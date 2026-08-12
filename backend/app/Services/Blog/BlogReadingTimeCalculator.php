<?php

namespace App\Services\Blog;

class BlogReadingTimeCalculator
{
    public function calculate(string $htmlOrText): int
    {
        $plain = trim(preg_replace('/\s+/u', ' ', strip_tags($htmlOrText)) ?? '');
        $words = $plain === '' ? 0 : count(preg_split('/\s+/u', $plain, -1, PREG_SPLIT_NO_EMPTY) ?: []);
        $wpm = max(60, (int) config('blog.reading_words_per_minute', 180));

        return max(1, (int) ceil($words / $wpm));
    }
}
