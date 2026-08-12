<?php

namespace Tests\Unit;

use App\Models\Seo\SeoLocation;
use App\Services\Seo\LocationPageQualityGate;
use Tests\TestCase;

class Phase9LocalSeoServicesTest extends TestCase
{
    public function test_quality_gate_blocks_thin_location(): void
    {
        $loc = new SeoLocation([
            'name' => 'X',
            'slug' => 'x',
            'type' => 'city',
            'description' => 'کوتاه',
            'unique_value' => 'کم',
        ]);
        $gate = (new LocationPageQualityGate)->evaluate($loc);
        $this->assertFalse($gate['passed']);
        $this->assertNotEmpty($gate['blockers']);
        $this->assertStringContainsString('Google Score', $gate['note']);
    }

    public function test_quality_gate_blocks_unverified_coords(): void
    {
        $loc = new SeoLocation([
            'name' => 'تهران مرکز تست کیفیت محتوا برای صفحه محلی واقعی',
            'slug' => 'tehran-test-quality-page',
            'type' => 'city',
            'description' => str_repeat('توضیح واقعی درباره خدمات دفتر و نیاز کاربر محلی. ', 8),
            'unique_value' => str_repeat('ارزش منحصربه‌فرد محلی برای مشاوران این منطقه. ', 5),
            'latitude' => 35.7,
            'longitude' => 51.4,
            'coords_verified' => false,
        ]);
        $gate = (new LocationPageQualityGate)->evaluate($loc);
        $this->assertFalse($gate['passed']);
        $this->assertTrue(collect($gate['blockers'])->contains(fn ($b) => str_contains($b, 'coords_verified')));
    }

    public function test_performance_and_entity_types_documented_in_model(): void
    {
        $this->assertContains('BUSINESS', \App\Models\Seo\SeoEntity::TYPES);
        $this->assertContains('LOCATION', \App\Models\Seo\SeoEntity::TYPES);
        $this->assertContains('SERVES', \App\Models\Seo\SeoEntityRelationship::TYPES);
    }
}
