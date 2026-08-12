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
}
