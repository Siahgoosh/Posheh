<?php

namespace Tests\Unit;

use App\Models\VirtualTour;
use App\Models\VirtualTourScene;
use App\Modules\VirtualTour\Application\Contracts\PanoramaStorageInterface;
use App\Modules\VirtualTour\Application\Services\TourAnalyticsService;
use App\Modules\VirtualTour\Application\Services\TourSeoService;
use PHPUnit\Framework\TestCase;

class TourSeoServiceTest extends TestCase
{
    public function test_meta_for_tour_includes_scene_urls(): void
    {
        $storage = $this->createMock(PanoramaStorageInterface::class);
        $storage->method('url')->willReturn('https://cdn.example/panorama.jpg');

        $service = new TourSeoService($storage);

        $tour = new VirtualTour([
            'title' => 'تور تست',
            'slug' => 'test-tour',
            'description' => 'توضیحات',
            'status' => 'published',
            'visibility' => 'public',
            'tour_type' => 'smart_walk',
        ]);

        $scene = new VirtualTourScene([
            'id' => 42,
            'name' => 'اتاق نشیمن',
            'is_visible' => true,
            'sort_order' => 0,
        ]);
        $scene->id = 42;

        $tour->setRelation('scenes', collect([$scene]));

        $meta = $service->metaForTour($tour);

        $this->assertSame('تور تست', $meta['title']);
        $this->assertFalse($meta['noindex']);
        $this->assertCount(1, $meta['scene_urls']);
        $this->assertSame(42, $meta['scene_urls'][0]['scene_id']);
        $this->assertStringContainsString('/scene/42', $meta['scene_urls'][0]['url']);
        $this->assertIsArray($meta['json_ld']);
    }
}

class TourAnalyticsServiceTest extends TestCase
{
    public function test_event_types_include_engagement_events(): void
    {
        $types = TourAnalyticsService::EVENT_TYPES;

        $this->assertContains('scene_view', $types);
        $this->assertContains('hotspot_click', $types);
        $this->assertContains('tour_complete', $types);
        $this->assertContains('session_start', $types);
    }
}
