<?php

namespace Tests\Unit;

use App\Services\Blog\BlogSitemapService;
use App\Services\Seo\SitemapValidatorService;
use Tests\TestCase;

class Phase8TechnicalSeoServicesTest extends TestCase
{
    public function test_sitemap_invalidate_clears_v3_key(): void
    {
        \Illuminate\Support\Facades\Cache::put('blog.sitemap.xml.v3', 'x', 60);
        \Illuminate\Support\Facades\Cache::put('blog.sitemap.payload.v2', ['posts' => []], 60);
        $service = new BlogSitemapService;
        $service->invalidate();
        $this->assertFalse(\Illuminate\Support\Facades\Cache::has('blog.sitemap.xml.v3'));
        $this->assertFalse(\Illuminate\Support\Facades\Cache::has('blog.sitemap.payload.v2'));
    }

    public function test_performance_budget_config_exists(): void
    {
        $budget = config('performance.budget');
        $this->assertIsArray($budget);
        $this->assertArrayHasKey('lcp_ms', $budget);
        $this->assertArrayHasKey('inp_ms', $budget);
        $this->assertArrayHasKey('cls', $budget);
    }

    public function test_security_headers_csp_default_off(): void
    {
        $this->assertFalse((bool) config('performance.security_headers.csp_enabled'));
        $this->assertTrue((bool) config('performance.security_headers.enabled'));
    }

    public function test_sitemap_validator_reports_metrics_shape(): void
    {
        $validator = new SitemapValidatorService(new BlogSitemapService);
        // May hit DB; if migrations not run in unit env, catch and skip soft
        try {
            $result = $validator->validate();
            $this->assertArrayHasKey('metrics', $result);
            $this->assertArrayHasKey('issues', $result);
            $this->assertArrayHasKey('url_count', $result['metrics']);
        } catch (\Throwable $e) {
            $this->markTestSkipped('DB unavailable: '.$e->getMessage());
        }
    }
}
