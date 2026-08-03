<?php

return [
    'max_panorama_size_mb' => (int) env('VT_MAX_PANORAMA_MB', 100),
    'max_media_size_mb' => (int) env('VT_MAX_MEDIA_MB', 50),
    'allowed_panorama_mimes' => ['image/jpeg', 'image/jpg', 'image/png', 'image/x-png', 'image/webp'],
    'cdn_url' => env('VT_CDN_URL'),
    'cache_ttl' => (int) env('VT_CACHE_TTL', 3600),
    'version_retention' => (int) env('VT_VERSION_RETENTION', 20),
    'export_disk' => 'local',
];
