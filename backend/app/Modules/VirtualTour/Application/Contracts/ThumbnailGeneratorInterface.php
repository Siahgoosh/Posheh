<?php

namespace App\Modules\VirtualTour\Application\Contracts;

interface ThumbnailGeneratorInterface
{
    /**
     * @return array{thumbnail_path: string, width: int, height: int}|null
     */
    public function generate(string $sourcePath, int $tourId, int $sceneId): ?array;
}
