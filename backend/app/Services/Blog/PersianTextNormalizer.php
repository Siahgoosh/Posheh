<?php

namespace App\Services\Blog;

class PersianTextNormalizer
{
    public function normalize(string $text): string
    {
        $map = [
            'ي' => 'ی',
            'ك' => 'ک',
            'ة' => 'ه',
            'ۀ' => 'ه',
            '‌' => ' ', // ZWNJ → space for search tolerance
            '‏' => '',
            '‎' => '',
        ];
        $text = strtr($text, $map);
        $text = mb_strtolower($text);
        $text = preg_replace('/\s+/u', ' ', $text) ?? '';

        return trim($text);
    }

    public function contains(string $haystack, string $needle): bool
    {
        if ($needle === '') {
            return true;
        }

        return mb_stripos($this->normalize($haystack), $this->normalize($needle)) !== false;
    }

    /**
     * Light editorial normalize for content save (ي→ی, ك→ک, collapse spaces).
     * Keeps ZWNJ; does not destroy user text beyond character fixes.
     */
    public function normalizeEditorial(string $text): string
    {
        $map = [
            'ي' => 'ی',
            'ك' => 'ک',
            'ة' => 'ه',
            'ۀ' => 'ه',
            '‏' => '',
            '‎' => '',
        ];
        $text = strtr($text, $map);
        // Collapse 3+ spaces / broken whitespace outside tags roughly
        $text = preg_replace("/[ \t]{2,}/u", ' ', $text) ?? $text;
        $text = preg_replace("/\n{3,}/u", "\n\n", $text) ?? $text;

        return $text;
    }
}
