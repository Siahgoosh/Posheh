<?php

namespace Tests\Unit;

use App\Services\Blog\BlogContentQualityScorer;
use App\Services\Blog\BlogQualityGate;
use App\Services\Blog\Rebuild\Phase4Batch1RebuiltArticles;
use Tests\TestCase;

class BlogContentRebuildPhase4Batch1Test extends TestCase
{
    public function test_phase4_batch1_articles_pass_quality_gate_as_drafts(): void
    {
        $scorer = new BlogContentQualityScorer;
        $gate = new BlogQualityGate($scorer);
        $articles = (new Phase4Batch1RebuiltArticles)->all();

        $this->assertCount(5, $articles);

        foreach ($articles as $article) {
            $this->assertFalse($article['is_published']);
            $this->assertTrue($article['rebuild_locked']);
            $this->assertSame('content_review', $article['review_status']);
            $this->assertStringContainsString('noindex', (string) $article['robots_directive']);

            $scores = $scorer->score($article);
            $result = $gate->evaluate($article, forPublish: false);

            $this->assertTrue($result['passed'], $article['slug'].': '.implode('; ', $result['blockers']));
            $this->assertGreaterThanOrEqual(60, $scores['overall'], $article['slug'].' overall too low: '.$scores['overall']);
            $this->assertGreaterThanOrEqual(320, $scores['metrics']['word_count'], $article['slug']);
            $this->assertGreaterThanOrEqual(2, $scores['metrics']['internal_links'], $article['slug']);
            $this->assertNotEmpty($article['faq']);
            $this->assertNotEmpty($article['cover_image']);
            $this->assertNotEmpty($article['focus_keyword']);
        }
    }
}
