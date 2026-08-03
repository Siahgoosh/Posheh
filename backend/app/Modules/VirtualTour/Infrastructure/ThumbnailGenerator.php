<?php

namespace App\Modules\VirtualTour\Infrastructure;

use App\Modules\VirtualTour\Application\Contracts\ThumbnailGeneratorInterface;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ThumbnailGenerator implements ThumbnailGeneratorInterface
{
    public function generate(string $sourcePath, int $tourId, int $sceneId): ?array
    {
        if (str_starts_with($sourcePath, 'demo/') || str_starts_with($sourcePath, 'http')) {
            return null;
        }

        $disk = Storage::disk('public');
        if (! $disk->exists($sourcePath)) {
            return null;
        }

        $fullPath = $disk->path($sourcePath);
        $imageInfo = @getimagesize($fullPath);
        if (! $imageInfo) {
            return null;
        }

        [$width, $height, $type] = $imageInfo;
        $source = match ($type) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($fullPath),
            IMAGETYPE_PNG => @imagecreatefrompng($fullPath),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($fullPath) : false,
            default => false,
        };

        if (! $source) {
            return null;
        }

        $thumbWidth = 320;
        $thumbHeight = 160;
        $thumb = imagecreatetruecolor($thumbWidth, $thumbHeight);

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
    }
}
