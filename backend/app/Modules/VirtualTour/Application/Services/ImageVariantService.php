<?php

namespace App\Modules\VirtualTour\Application\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Generates responsive image variants (thumbnail, medium, large, ultra) from uploads.
 * Supports JPEG, PNG, WebP input; outputs optimized JPEG/WebP variants.
 */
class ImageVariantService
{
    public const VARIANT_THUMBNAIL = 'thumbnail';

    public const VARIANT_MEDIUM = 'medium';

    public const VARIANT_LARGE = 'large';

    public const VARIANT_ULTRA = 'ultra';

    /** @return array<string, int> */
    public function variantMaxWidths(): array
    {
        return config('virtual-tour.image_variants', [
            self::VARIANT_THUMBNAIL => 320,
            self::VARIANT_MEDIUM => 1280,
            self::VARIANT_LARGE => 2560,
            self::VARIANT_ULTRA => 4096,
        ]);
    }

    public function maxDimension(): int
    {
        return (int) config('virtual-tour.max_image_dimension', 12000);
    }

    /**
     * @return array{
     *   original: string,
     *   thumbnail: string|null,
     *   medium: string|null,
     *   large: string|null,
     *   ultra: string|null,
     *   width: int,
     *   height: int,
     *   format: string
     * }|null
     */
    public function generateVariants(string $sourceAbsolutePath, int $tourId, int $sceneId): ?array
    {
        if (! extension_loaded('gd')) {
            return null;
        }

        $imageInfo = @getimagesize($sourceAbsolutePath);
        if (! $imageInfo) {
            return null;
        }

        [$width, $height, $type] = $imageInfo;

        if ($width > $this->maxDimension() || $height > $this->maxDimension()) {
            throw new \InvalidArgumentException(
                'حداکثر اندازه تصویر '.number_format($this->maxDimension()).' پیکسل است.'
            );
        }

        $source = $this->createImageFromFile($sourceAbsolutePath, $type);
        if (! $source) {
            return null;
        }

        $disk = Storage::disk('public');
        $baseDir = "virtual-tours/{$tourId}/scenes/{$sceneId}";
        $uuid = Str::uuid()->toString();

        $variants = [
            'width' => $width,
            'height' => $height,
            'format' => 'jpeg',
        ];

        $maxEdge = max($width, $height);

        foreach ($this->variantMaxWidths() as $name => $targetWidth) {
            if ($maxEdge <= $targetWidth && $name !== self::VARIANT_THUMBNAIL) {
                $variants[$name] = null;

                continue;
            }

            $scale = min(1, $targetWidth / $maxEdge);
            $newW = max(1, (int) round($width * $scale));
            $newH = max(1, (int) round($height * $scale));

            $resized = imagecreatetruecolor($newW, $newH);
            if ($type === IMAGETYPE_PNG) {
                imagealphablending($resized, false);
                imagesavealpha($resized, true);
            }

            imagecopyresampled($resized, $source, 0, 0, 0, 0, $newW, $newH, $width, $height);

            $relativePath = "{$baseDir}/{$name}-{$uuid}.jpg";
            $absolutePath = $disk->path($relativePath);

            if (! is_dir(dirname($absolutePath))) {
                mkdir(dirname($absolutePath), 0755, true);
            }

            imagejpeg($resized, $absolutePath, $this->jpegQuality($name));
            imagedestroy($resized);

            $variants[$name] = $relativePath;
        }

        imagedestroy($source);

        return $variants;
    }

    /**
     * @param  array<string, string|null>  $variants
     */
    public function deleteVariants(array $variants): void
    {
        $disk = Storage::disk('public');
        $keys = [self::VARIANT_THUMBNAIL, self::VARIANT_MEDIUM, self::VARIANT_LARGE, self::VARIANT_ULTRA];

        foreach ($keys as $key) {
            $path = $variants[$key] ?? null;
            if ($path && $disk->exists($path)) {
                $disk->delete($path);
            }
        }
    }

    private function jpegQuality(string $variant): int
    {
        return match ($variant) {
            self::VARIANT_THUMBNAIL => 78,
            self::VARIANT_MEDIUM => 82,
            self::VARIANT_LARGE => 85,
            default => 88,
        };
    }

    private function createImageFromFile(string $path, int $type): \GdImage|false
    {
        return match ($type) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($path),
            IMAGETYPE_PNG => @imagecreatefrompng($path),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : false,
            IMAGETYPE_AVIF => function_exists('imagecreatefromavif') ? @imagecreatefromavif($path) : false,
            default => false,
        };
    }
}
