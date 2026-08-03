<?php

namespace App\Providers;

use App\Modules\VirtualTour\Application\Contracts\PanoramaStorageInterface;
use App\Modules\VirtualTour\Application\Contracts\ThumbnailGeneratorInterface;
use App\Modules\VirtualTour\Infrastructure\PanoramaStorage;
use App\Modules\VirtualTour\Infrastructure\ThumbnailGenerator;
use Illuminate\Support\ServiceProvider;

class VirtualTourServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PanoramaStorageInterface::class, PanoramaStorage::class);
        $this->app->singleton(ThumbnailGeneratorInterface::class, ThumbnailGenerator::class);
    }
}
