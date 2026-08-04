<?php

namespace App\Modules\VirtualTour\Application\Contracts;

use Illuminate\Http\UploadedFile;

interface PanoramaStorageInterface
{
    public function store(int $tourId, UploadedFile $file, string $subdir = 'panoramas'): string;

    public function delete(string $path): void;

    public function url(string $path): string;
}
