<?php

return [
    'reading_words_per_minute' => (int) env('BLOG_READING_WPM', 180),

    'related_weights' => [
        'topic' => 40,
        'semantic' => 25,
        'category' => 15,
        'keywords' => 10,
        'freshness' => 10,
    ],

    'popular' => [
        'views_weight' => 0.5,
        'recency_weight' => 0.3,
        'engagement_weight' => 0.2,
        'days_window' => 90,
    ],

    'search' => [
        'min_query_length' => 2,
        'per_page' => 12,
    ],

    'sitemap_cache_ttl' => (int) env('BLOG_SITEMAP_CACHE_TTL', 300),

    'gsc' => [
        'enabled' => (bool) env('BLOG_GSC_ENABLED', false),
        'property' => env('BLOG_GSC_PROPERTY', 'https://posheapp.ir/'),
        // Credentials path — leave empty for placeholder mode
        'credentials_json' => env('BLOG_GSC_CREDENTIALS_JSON'),
    ],

    'upload' => [
        'max_kb' => 8192,
        'allowed_mimes' => ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/avif'],
    ],
];
