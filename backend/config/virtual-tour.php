<?php

return [
    'max_panorama_size_mb' => (int) env('VT_MAX_PANORAMA_MB', 100),
    'max_media_size_mb' => (int) env('VT_MAX_MEDIA_MB', 50),
    'allowed_panorama_mimes' => ['image/jpeg', 'image/jpg', 'image/png', 'image/x-png', 'image/webp'],
    'allowed_scene_image_mimes' => ['image/jpeg', 'image/jpg', 'image/png', 'image/x-png', 'image/webp', 'image/avif'],
    'max_scene_image_size_mb' => (int) env('VT_MAX_SCENE_IMAGE_MB', 50),
    'max_image_dimension' => (int) env('VT_MAX_IMAGE_DIMENSION', 12000),
    'image_variants' => [
        'thumbnail' => 320,
        'medium' => 1280,
        'large' => 2560,
        'ultra' => 4096,
    ],
    'cdn_url' => env('VT_CDN_URL'),
    'cache_ttl' => (int) env('VT_CACHE_TTL', 3600),
    'version_retention' => (int) env('VT_VERSION_RETENTION', 20),
    'export_disk' => 'local',
];
