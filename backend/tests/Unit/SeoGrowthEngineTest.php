<?php

namespace Tests\Unit;

use App\Services\Seo\SeoPriorityScorer;
use App\Services\Seo\SeoQueryIntelligence;
use App\Services\Blog\PersianTextNormalizer;
use Tests\TestCase;

class SeoGrowthEngineTest extends TestCase
{
    public function test_priority_score_prefers_high_impact_low_effort(): void
    {
        $scorer = new SeoPriorityScorer;
        $quick = $scorer->score(80, 70, 'low');
        $hard = $scorer->score(80, 70, 'high');
        $this->assertGreaterThan($hard, $quick);
        $this->assertSame('HIGH', $scorer->priorityLabel($quick));
    }

    public function test_query_normalization_keeps_raw_and_normalizes_arabic_yeh(): void
    {
        $intel = new SeoQueryIntelligence(new PersianTextNormalizer);
        $out = $intel->normalizeQuery("نرم افزار سى آر ام"); // Arabic Yeh in سى
        $this->assertSame("نرم افزار سى آر ام", $out['query_raw']);
        $this->assertStringNotContainsString("\u{064A}", $out['query_normalized']); // Arabic Yeh removed
        $this->assertStringContainsString('ی', $out['query_normalized']);
    }

    public function test_query_intent_classification(): void
    {
        $intel = new SeoQueryIntelligence(new PersianTextNormalizer);
        $crm = $intel->classify('بهترین نرم افزار CRM املاک');
        $this->assertSame('crm', $crm['topic']);
        $this->assertContains($crm['intent'], ['informational', 'commercial']);

        $buy = $intel->classify('قیمت اشتراک نرم افزار املاک');
        $this->assertSame('commercial', $buy['intent']);
        $this->assertSame('MOFU', $buy['funnel_stage']);
    }

    public function test_cluster_key_uses_intent_and_topic(): void
    {
        $intel = new SeoQueryIntelligence(new PersianTextNormalizer);
        $key = $intel->clusterKey('خرید نرم افزار crm املاک', 'commercial', 'crm');
        $this->assertStringStartsWith('crm|commercial|', $key);
    }
}
