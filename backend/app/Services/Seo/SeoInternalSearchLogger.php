<?php

namespace App\Services\Seo;

use App\Models\Seo\SeoInternalSearch;
use App\Services\Blog\PersianTextNormalizer;
use Illuminate\Support\Facades\Schema;

class SeoInternalSearchLogger
{
    public function __construct(
        private readonly PersianTextNormalizer $normalizer,
    ) {}

    public function log(string $query, int $resultsCount, ?string $ip = null): void
    {
        if (! Schema::hasTable('seo_internal_searches')) {
            return;
        }
        if (! config('seo.internal_search_logging', true)) {
            return;
        }
        $query = trim($query);
        if (mb_strlen($query) < 2) {
            return;
        }

        SeoInternalSearch::query()->create([
            'query_raw' => mb_substr($query, 0, 255),
            'query_normalized' => $this->normalizer->normalize($query),
            'results_count' => max(0, $resultsCount),
            'zero_result' => $resultsCount === 0,
            'ip_hash' => $ip ? hash('sha256', $ip.'|'.(string) config('app.key')) : null,
            'searched_at' => now(),
        ]);
    }
}
