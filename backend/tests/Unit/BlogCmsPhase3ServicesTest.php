<?php

namespace Tests\Unit;

use App\Services\Blog\BlogRelatedArticlesService;
use App\Services\Blog\BlogSearchService;
use App\Services\Blog\PersianTextNormalizer;
use App\Services\Blog\BlogReadingTimeCalculator;
use Tests\TestCase;

class BlogCmsPhase3ServicesTest extends TestCase
{
    public function test_persian_normalizer_handles_ye_kaf_and_zwnj(): void
    {
        $n = new PersianTextNormalizer;
        $this->assertTrue($n->contains('نرم‌افزار املاك', 'نرم افزار املاک'));
        $this->assertSame('کلمه ی تست', $n->normalize("كلمه‌ي تست"));
    }

    public function test_reading_time_uses_config_wpm(): void
    {
        config(['blog.reading_words_per_minute' => 200]);
        $calc = new BlogReadingTimeCalculator;
        $html = '<p>'.str_repeat('کلمه ', 400).'</p>';
        $this->assertSame(2, $calc->calculate($html));
    }

    public function test_search_service_rejects_short_query(): void
    {
        $service = new BlogSearchService(new PersianTextNormalizer);
        $result = $service->search('ا');
        $this->assertSame(0, $result['meta']['total']);
    }
}
