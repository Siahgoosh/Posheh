<?php

namespace Tests\Unit;

use App\Services\Cro\CroLeadService;
use App\Services\Cro\CroConversionTracker;
use Tests\TestCase;

class CroLeadScoringTest extends TestCase
{
    public function test_demo_and_buy_score_higher_than_other(): void
    {
        $service = new CroLeadService(new CroConversionTracker);
        $demo = $service->score(['request_type' => 'DEMO', 'article_slug' => 'x', 'city' => 'tehran']);
        $other = $service->score(['request_type' => 'OTHER']);
        $this->assertGreaterThan($other, $demo);
        $this->assertGreaterThanOrEqual(20, $other);
        $this->assertLessThanOrEqual(100, $demo);
    }

    public function test_commercial_intent_bonus(): void
    {
        $service = new CroLeadService(new CroConversionTracker);
        $base = $service->score(['request_type' => 'OTHER']);
        $commercial = $service->score(['request_type' => 'OTHER', 'intent' => 'commercial']);
        $this->assertGreaterThan($base, $commercial);
    }
}
