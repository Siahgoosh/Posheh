<?php

namespace App\Services\Blog;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BlogCoverImageService
{
    public const WIDTH = 1200;

    public const HEIGHT = 630;

    /** @param array<int, string> $sourceUrls */
    public function generate(string $slug, string $title, array $sourceUrls, int $index): string
    {
        $relative = 'blog/covers/'.$slug.'.jpg';
        $disk = Storage::disk('public');

        if ($disk->exists($relative) && $disk->size($relative) > 10_000) {
            return '/storage/'.$relative;
        }

        $source = $sourceUrls[$index % count($sourceUrls)] ?? $sourceUrls[0];

        try {
            $binary = Http::timeout(25)->get($source)->body();
            if ($binary === '' || strlen($binary) < 1000) {
                throw new \RuntimeException('Empty image response');
            }

            $image = @imagecreatefromstring($binary);
            if ($image === false) {
                throw new \RuntimeException('Invalid image data');
            }

            $resized = $this->fitCover($image, self::WIDTH, self::HEIGHT);
            $withOverlay = $this->applyBrandOverlay($resized, $title);

            ob_start();
            imagejpeg($withOverlay, null, 86);
            $jpeg = ob_get_clean() ?: '';

            imagedestroy($image);
            imagedestroy($resized);
            imagedestroy($withOverlay);

            $disk->put($relative, $jpeg);

            return '/storage/'.$relative;
        } catch (\Throwable $e) {
            Log::warning('Blog cover generation failed', ['slug' => $slug, 'error' => $e->getMessage()]);

            return $this->generateFallback($slug, $title, $relative, $disk);
        }
    }

    private function fitCover(\GdImage $src, int $targetW, int $targetH): \GdImage
    {
        $srcW = imagesx($src);
        $srcH = imagesy($src);
        $srcRatio = $srcW / max(1, $srcH);
        $targetRatio = $targetW / $targetH;

        if ($srcRatio > $targetRatio) {
            $newH = $srcH;
            $newW = (int) round($srcH * $targetRatio);
            $srcX = (int) round(($srcW - $newW) / 2);
            $srcY = 0;
        } else {
            $newW = $srcW;
            $newH = (int) round($srcW / $targetRatio);
            $srcX = 0;
            $srcY = (int) round(($srcH - $newH) / 2);
        }

        $dst = imagecreatetruecolor($targetW, $targetH);
        imagecopyresampled($dst, $src, 0, 0, $srcX, $srcY, $targetW, $targetH, $newW, $newH);

        return $dst;
    }

    private function applyBrandOverlay(\GdImage $image, string $title): \GdImage
    {
        $overlay = imagecolorallocatealpha($image, 15, 23, 42, 55);
        imagefilledrectangle($image, 0, (int) (self::HEIGHT * 0.55), self::WIDTH, self::HEIGHT, $overlay);

        $white = imagecolorallocate($image, 255, 255, 255);
        $accent = imagecolorallocate($image, 129, 140, 248);
        imagestring($image, 5, 40, (int) (self::HEIGHT * 0.58), 'POSHEH | POOSHEH.APP.IR', $accent);

        $asciiTitle = $this->asciiTitle($title);
        $lines = $this->wrapText($asciiTitle, 52);
        $y = (int) (self::HEIGHT * 0.66);
        foreach (array_slice($lines, 0, 3) as $line) {
            imagestring($image, 4, 40, $y, $line, $white);
            $y += 22;
        }

        return $image;
    }

    /** @return array<int, string> */
    private function wrapText(string $text, int $maxLen): array
    {
        $words = preg_split('/\s+/', trim($text)) ?: [];
        $lines = [];
        $current = '';

        foreach ($words as $word) {
            $candidate = $current === '' ? $word : $current.' '.$word;
            if (strlen($candidate) > $maxLen && $current !== '') {
                $lines[] = $current;
                $current = $word;
            } else {
                $current = $candidate;
            }
        }

        if ($current !== '') {
            $lines[] = $current;
        }

        return $lines ?: ['Posheh Real Estate Blog'];
    }

    private function asciiTitle(string $title): string
    {
        $ascii = Str::ascii($title);
        $ascii = preg_replace('/[^\x20-\x7E]/', '', $ascii) ?? '';
        $ascii = trim(preg_replace('/\s+/', ' ', $ascii) ?? '');

        return $ascii !== '' ? $ascii : 'Posheh Real Estate Guide';
    }

    private function generateFallback(string $slug, string $title, string $relative, $disk): string
    {
        $image = imagecreatetruecolor(self::WIDTH, self::HEIGHT);
        $bg = imagecolorallocate($image, 30, 41, 59);
        imagefilledrectangle($image, 0, 0, self::WIDTH, self::HEIGHT, $bg);
        $withOverlay = $this->applyBrandOverlay($image, $title);
        ob_start();
        imagejpeg($withOverlay, null, 86);
        $jpeg = ob_get_clean() ?: '';
        imagedestroy($image);
        imagedestroy($withOverlay);
        $disk->put($relative, $jpeg);

        return '/storage/'.$relative;
    }
}
