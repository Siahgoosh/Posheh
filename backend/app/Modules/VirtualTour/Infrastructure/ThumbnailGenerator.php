<?php

namespace App\Modules\VirtualTour\Infrastructure;

use App\Modules\VirtualTour\Application\Contracts\ThumbnailGeneratorInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ThumbnailGenerator implements ThumbnailGeneratorInterface
{
    private const MAX_THUMB_BYTES = 25 * 1024 * 1024;

    public function generate(string $sourcePath, int $tourId, int $sceneId): ?array
    {
        try {
            if (str_starts_with($sourcePath, 'demo/') || str_starts_with($sourcePath, 'http')) {
                return null;
            }

            if (! extension_loaded('gd')) {
                return null;
            }

            $disk = Storage::disk('public');
            if (! $disk->exists($sourcePath)) {
                return null;
            }

            $fullPath = $disk->path($sourcePath);
            $fileSize = @filesize($fullPath) ?: 0;
            $imageInfo = @getimagesize($fullPath);
            if (! $imageInfo) {
                return null;
            }

            [$width, $height, $type] = $imageInfo;

            if ($fileSize > self::MAX_THUMB_BYTES || $width > 12000) {
                return [
                    'thumbnail_path' => null,
                    'width' => $width,
                    'height' => $height,
                ];
            }

            $source = match ($type) {
                IMAGETYPE_JPEG => @imagecreatefromjpeg($fullPath),
                IMAGETYPE_PNG => @imagecreatefrompng($fullPath),
                IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($fullPath) : false,
                default => false,
            };

            if (! $source) {
                return ['thumbnail_path' => null, 'width' => $width, 'height' => $height];
            }

            $thumbWidth = 320;
            $thumbHeight = 160;
            $thumb = imagecreatetruecolor($thumbWidth, $thumbHeight);

            if ($type === IMAGETYPE_PNG) {
                imagealphablending($thumb, false);
                imagesavealpha($thumb, true);
            }

            $cropX = max(0, (int) (($width - $height * 2) / 2));
            $cropWidth = min($width, $height * 2);

            imagecopyresampled(
                $thumb,
                $source,
                0,
                0,
                $cropX,
                0,
                $thumbWidth,
                $thumbHeight,
                $cropWidth,
                $height
            );

            $filename = "scene-{$sceneId}-".Str::random(8).'.jpg';
            $relativePath = "virtual-tours/{$tourId}/thumbnails/{$filename}";
            $absolutePath = $disk->path($relativePath);

            if (! is_dir(dirname($absolutePath))) {
                mkdir(dirname($absolutePath), 0755, true);
            }

            imagejpeg($thumb, $absolutePath, 82);
            imagedestroy($source);
            imagedestroy($thumb);

            return [
                'thumbnail_path' => $relativePath,
                'width' => $width,
                'height' => $height,
            ];
        } catch (\Throwable $e) {
            Log::warning('virtual-tour.thumbnail_failed', [
                'source' => $sourcePath,
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
