<?php

namespace Tests\Unit;

use App\Services\Blog\BlogAiAssistantService;
use App\Services\Blog\BlogPublishChecklistService;
use App\Services\Blog\BlogContentQualityScorer;
use App\Services\Blog\BlogQualityGate;
use App\Services\Blog\BlogRelatedArticlesService;
use App\Services\Blog\PersianTextNormalizer;
use Tests\TestCase;

class BlogCmsPhase7ServicesTest extends TestCase
{
    public function test_editorial_normalizer_keeps_zwnj_but_fixes_ye_kaf(): void
    {
        $n = new PersianTextNormalizer;
        $out = $n->normalizeEditorial("كلمه‌ي تست");
        $this->assertStringContainsString('کلمه', $out);
        $this->assertStringContainsString('ی', $out);
        $this->assertStringNotContainsString('ي', $out);
        $this->assertStringNotContainsString('ك', $out);
    }

    public function test_ai_assistant_never_marks_auto_publish_and_returns_outline(): void
    {
        $ai = new BlogAiAssistantService(
            new PersianTextNormalizer,
            new BlogRelatedArticlesService(new PersianTextNormalizer),
        );
        $result = $ai->assist('outline', ['title' => 'راهنمای CRM املاک', 'focus_keyword' => 'CRM']);
        $this->assertSame('outline', $result['action']);
        $this->assertArrayHasKey('sections', $result['result']);
        $this->assertStringContainsString('بازبینی انسانی', $result['result']['disclaimer']);
        $this->assertContains('brief', $ai->availableActions());
    }

    public function test_ai_meta_does_not_invent_claims(): void
    {
        $ai = new BlogAiAssistantService(
            new PersianTextNormalizer,
            new BlogRelatedArticlesService(new PersianTextNormalizer),
        );
        $result = $ai->assist('meta_description', [
            'title' => 'آزمون',
            'content' => '<p>این یک متن کوتاه آزمایشی درباره نرم‌افزار دفتر املاک است.</p>',
        ]);
        $this->assertArrayHasKey('text', $result['result']);
        $this->assertStringContainsString('ادعا اضافه نشده', $result['result']['note']);
    }

    public function test_publish_checklist_blocks_empty_title(): void
    {
        $service = new BlogPublishChecklistService(
            new BlogQualityGate(new BlogContentQualityScorer),
            new BlogContentQualityScorer,
        );
        $result = $service->evaluate([
            'title' => '',
            'slug' => 'bad',
            'content' => 'کوتاه',
        ]);
        $this->assertFalse($result['passed']);
        $this->assertNotEmpty($result['blocking']);
        $this->assertStringContainsString('Google Score نیست', $result['note']);
    }

    public function test_seo_analyzer_summary_is_not_google_score(): void
    {
        $analyzer = new \App\Services\Blog\BlogSeoAnalyzer;
        $out = $analyzer->analyze([
            'title' => 'عنوان مناسب برای تست سئوی داخلی پوشه',
            'slug' => 'test-seo-internal',
            'content' => '<h2>بخش</h2><p>'.str_repeat('متن مفید ', 80).'</p>',
            'meta_title' => 'عنوان سئوی داخلی تست طول مناسب پنجاه تا شصت',
            'meta_description' => str_repeat('توضیح ', 20),
            'excerpt' => 'خلاصه',
            'keywords' => 'تست',
            'cover_image' => '/x.jpg',
        ]);
        $this->assertStringNotContainsString('آماده انتشار در گوگل', $out['summary']);
        $this->assertStringContainsString('داخلی', $out['summary']);
    }
}
