<?php

return [
    /*
    | Content Operations / AI Content OS
    | AI is an ASSISTANT — never an autonomous publisher by default.
    */
    'default_provider' => env('CONTENT_AI_PROVIDER', 'local'),
    'openai_enabled' => (bool) env('CONTENT_AI_OPENAI_ENABLED', false),
    'openai_key' => env('CONTENT_AI_OPENAI_KEY', env('OPENAI_API_KEY', '')),
    'openai_model' => env('CONTENT_AI_OPENAI_MODEL', 'gpt-4o-mini'),
    'token_cost_toman_per_1k' => (int) env('CONTENT_AI_TOKEN_COST_TOMAN_PER_1K', 0),
    'allow_auto_publish' => (bool) env('CONTENT_AI_ALLOW_AUTO_PUBLISH', false),
    'allow_auto_insert_links' => (bool) env('CONTENT_AI_ALLOW_AUTO_INSERT_LINKS', false),
    'allow_auto_publish_images' => (bool) env('CONTENT_AI_ALLOW_AUTO_PUBLISH_IMAGES', false),
    'job_timeout_sec' => (int) env('CONTENT_AI_JOB_TIMEOUT', 90),
    'max_retries' => 3,
    'sensitive_claim_keywords' => [
        'قیمت', 'قانون', 'مالیات', 'وام', 'حقوق', 'آمار', 'مقررات', 'نرخ', 'درصد', 'تومان', 'ریال',
    ],
];
