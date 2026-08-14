<?php

return [
    'default_provider' => env('BLOG_IMAGE_PROVIDER', 'mock'),
    'fallback_provider' => env('BLOG_IMAGE_FALLBACK', 'mock'),
    'openai_enabled' => (bool) env('BLOG_IMAGE_OPENAI_ENABLED', false),
    'openai_key' => env('BLOG_IMAGE_OPENAI_KEY', env('OPENAI_API_KEY', '')),
    'openai_model' => env('BLOG_IMAGE_OPENAI_MODEL', 'dall-e-3'),
    'openai_cost_toman' => (int) env('BLOG_IMAGE_OPENAI_COST_TOMAN', 0),
    'default_resolution' => env('BLOG_IMAGE_RESOLUTION', '1792x1024'),
    'default_aspect' => '16:9',
    'auto_approve' => (bool) env('BLOG_IMAGE_AUTO_APPROVE', false),
    'require_budget' => (bool) env('BLOG_IMAGE_REQUIRE_BUDGET', true),
    'batch_size' => (int) env('BLOG_IMAGE_BATCH_SIZE', 50),
    'max_retries' => 3,
    'storage_disk' => env('BLOG_IMAGE_DISK', 'public'),
    'brand_style' => 'Professional, modern, clean, premium real-estate/SaaS, Persian-audience friendly, documentary lighting, no plastic faces, no fake logos, no readable fake KPIs',
    'negative_prompt' => 'fake logo, watermark, readable fake text, distorted anatomy, extra fingers, plastic faces, oversaturated, cheap stock, misleading property claim, Persian text in image',
];
