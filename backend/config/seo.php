<?php

return [
    'effort_weights' => [
        'low' => 1.0,
        'medium' => 2.0,
        'high' => 3.5,
    ],
    'priority_thresholds' => [
        'urgent' => 70,
        'high' => 40,
        'medium' => 20,
    ],
    'quick_win' => [
        'min_impressions' => (int) env('SEO_QW_MIN_IMPRESSIONS', 50),
        'max_ctr' => (float) env('SEO_QW_MAX_CTR', 0.05),
    ],
    'striking' => [
        'min_impressions' => (int) env('SEO_SD_MIN_IMPRESSIONS', 40),
    ],
    'internal_search_logging' => (bool) env('SEO_INTERNAL_SEARCH_LOGGING', true),
    'automation' => [
        'automatic_allowed' => [
            'sitemap_refresh',
            'cache_refresh',
            'broken_link_scan',
            'seo_monitoring',
            'data_collection',
        ],
    ],
    'refresh_protection' => [
        'block_auto_rewrite_for_portfolio' => ['STAR'],
    ],
];
