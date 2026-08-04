<?php

namespace Tests\Unit;

use App\Modules\VirtualTour\Domain\SceneType;
use App\Modules\VirtualTour\Domain\TourType;
use PHPUnit\Framework\TestCase;

class SmartWalkTourTypeTest extends TestCase
{
    public function test_tour_type_default_scene_type(): void
    {
        $this->assertSame(SceneType::FlatImage, TourType::SmartWalk->defaultSceneType());
        $this->assertSame(SceneType::Equirectangular, TourType::Panorama360->defaultSceneType());
    }

    public function test_tour_type_values(): void
    {
        $this->assertSame('smart_walk', TourType::SmartWalk->value);
        $this->assertSame('panorama_360', TourType::Panorama360->value);
    }
}
