<?php

namespace Tests\Unit;

use App\Modules\VirtualTour\Infrastructure\PanoramaStorage;
use Tests\TestCase;

class PanoramaStorageUrlTest extends TestCase
{
    public function test_demo_path_returns_relative_url(): void
    {
        $storage = new PanoramaStorage();

        $this->assertSame('/demo/sphere.jpg', $storage->url('demo/sphere.jpg'));
    }

    public function test_storage_path_returns_relative_url(): void
    {
        $storage = new PanoramaStorage();

        $this->assertSame(
            '/storage/virtual-tours/1/panoramas/test.jpg',
            $storage->url('virtual-tours/1/panoramas/test.jpg'),
        );
    }
}
