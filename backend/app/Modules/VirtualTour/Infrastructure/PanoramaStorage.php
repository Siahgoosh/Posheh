<?php

namespace App\Modules\VirtualTour\Infrastructure;

use App\Modules\VirtualTour\Application\Contracts\PanoramaStorageInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class PanoramaStorage implements PanoramaStorageInterface
{
    public function store(int $tourId, UploadedFile $file, string $subdir = 'panoramas'): string
    {
        return $file->store("virtual-tours/{$tourId}/{$subdir}", 'public');
    }

    public function delete(string $path): void
    {
        if (str_starts_with($path, 'http') || str_starts_with($path, 'demo/')) {
            return;
        }

        Storage::disk('public')->delete($path);
    }

    public function url(string $path): string
    {
        if (str_starts_with($path, 'http')) {
            return $path;
        }

        if (str_starts_with($path, 'demo/')) {
            return '/demo/'.basename($path);
        }

        $cdn = config('virtual-tour.cdn_url');
        if ($cdn) {
            return rtrim($cdn, '/').'/storage/'.$path;
        }

        return '/storage/'.$path;
    }
}
