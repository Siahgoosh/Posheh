<?php

namespace App\Services\ContentOps;

/**
 * Sanitize AI HTML/Markdown/JSON before it can enter articles.
 */
class AiOutputSanitizer
{
    public function sanitizeHtml(string $html): string
    {
        // Strip dangerous tags/attrs
        $html = preg_replace('#<(script|iframe|object|embed|form|link|meta|style)[^>]*>.*?</\1>#is', '', $html) ?? $html;
        $html = preg_replace('#<(script|iframe|object|embed|form|link|meta|style)[^>]*/?>#is', '', $html) ?? $html;
        $html = preg_replace('/\son\w+\s*=\s*(["\']).*?\1/iu', '', $html) ?? $html;
        $html = preg_replace('/javascript\s*:/iu', '', $html) ?? $html;
        $html = preg_replace('/data\s*:\s*text\/html/iu', '', $html) ?? $html;

        return $html;
    }

    public function looksLikeManipulation(string $text): array
    {
        $warnings = [];
        $plain = strip_tags($text);
        if (preg_match('/display\s*:\s*none|visibility\s*:\s*hidden|font-size\s*:\s*0/iu', $text)) {
            $warnings[] = 'Possible hidden text / SEO manipulation';
        }
        if (preg_match('/(عالی|۵\s*ستاره|5\s*star).{0,40}(جعلی|فیک|ساختگی)/iu', $plain)) {
            $warnings[] = 'Possible fake review language';
        }
        // Extreme keyword repetition
        $words = preg_split('/\s+/u', mb_strtolower($plain)) ?: [];
        $freq = array_count_values(array_filter($words, fn ($w) => mb_strlen($w) > 3));
        arsort($freq);
        $top = array_slice($freq, 0, 1, true);
        foreach ($top as $w => $c) {
            if ($c > 25 && count($words) > 100) {
                $warnings[] = 'Possible keyword stuffing around «'.$w.'»';
            }
        }

        return $warnings;
    }

    /** @param mixed $json */
    public function validateJson($json): ?array
    {
        if (is_array($json)) {
            return $json;
        }
        if (! is_string($json)) {
            return null;
        }
        $decoded = json_decode($json, true);

        return is_array($decoded) ? $decoded : null;
    }
}
