<?php

namespace Tests\Unit;

use App\Services\Blog\BlogSeoAnalyzer;
use Tests\TestCase;

class BlogSeoAnalyzerTest extends TestCase
{
    public function test_analyzer_returns_score_and_checks(): void
    {
        $analyzer = new BlogSeoAnalyzer;

        $result = $analyzer->analyze([
            'title' => 'راهنمای کامل نرم افزار املاک پوشه برای مشاوران حرفه‌ای',
            'meta_title' => 'نرم افزار املاک پوشه | CRM مشاور املاک حرفه‌ای ایران',
            'meta_description' => str_repeat('سئو ', 30),
            'excerpt' => str_repeat('خلاصه مقاله ', 10),
            'content' => '<h2>عنوان</h2><p>'.str_repeat('نرم افزار املاک ', 80).'</p><a href="/blog">لینک</a>',
            'slug' => 'real-estate-crm-guide',
            'keywords' => 'نرم افزار املاک, CRM',
            'cover_image' => 'https://example.com/cover.jpg',
        ]);

        $this->assertArrayHasKey('score', $result);
        $this->assertGreaterThan(50, $result['score']);
        $this->assertNotEmpty($result['checks']);
    }
}
