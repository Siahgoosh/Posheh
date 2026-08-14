<?php

namespace Tests\Unit;

use App\Services\Blog\BlogContentQualityScorer;
use App\Services\Blog\BlogQualityGate;
use App\Services\Blog\Rebuild\Batch1RebuiltArticles;
use Tests\TestCase;

class BlogContentRebuildBatch1Test extends TestCase
{
    public function test_batch1_articles_pass_quality_gate_as_drafts(): void
    {
        $scorer = new BlogContentQualityScorer;
        $gate = new BlogQualityGate($scorer);
        $articles = (new Batch1RebuiltArticles)->all();

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
            $this->assertGreaterThanOrEqual(500, $scores['metrics']['word_count'], $article['slug']);
            $this->assertGreaterThanOrEqual(2, $scores['metrics']['internal_links'], $article['slug']);
            $this->assertNotEmpty($article['faq']);
            $this->assertNotEmpty($article['cover_image']);
            $this->assertNotEmpty($article['focus_keyword']);
        }
    }

    public function test_template_thin_content_scores_lower_than_rebuild(): void
    {
        $scorer = new BlogContentQualityScorer;
        $thin = [
            'title' => 'راهنمای جامع آپارتمان در تهران',
            'content' => '<h2>مقدمه</h2><p>بازار املاک تهران. در دنیای امروز لازم به ذکر است که...</p>',
            'meta_title' => 'کوتاه',
            'meta_description' => 'کوتاه',
            'excerpt' => 'کوتاه',
            'keywords' => 'املاک',
        ];
        $rebuild = (new Batch1RebuiltArticles)->all()[0];

        $this->assertGreaterThan(
            $scorer->score($thin)['overall'],
            $scorer->score($rebuild)['overall']
        );
    }
}
