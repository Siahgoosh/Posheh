<?php

return [
    'default_provider' => env('AI_PROVIDER', 'rules'),

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
        'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
    ],

    'cache_ttl' => (int) env('AI_CACHE_TTL', 3600),

    'types' => [
        'reels_script', 'content_ideas', 'content_calendar', 'story_script',
        'caption', 'hashtags', 'publish_time', 'ad_text', 'whatsapp_message',
        'promote_property', 'market_analysis', 'campaign_suggestion', 'video_script',
        'stale_property_analysis', 'daily_plan', 'cover_text', 'seasonal_campaign',
    ],
];
