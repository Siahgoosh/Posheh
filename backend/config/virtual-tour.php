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
    'signed_urls' => (bool) env('VT_SIGNED_URLS', false),
    'signed_url_ttl_minutes' => (int) env('VT_SIGNED_URL_TTL_MINUTES', 120),
    'disable_direct_download' => (bool) env('VT_DISABLE_DIRECT_DOWNLOAD', true),
    'watermark_enabled_default' => (bool) env('VT_WATERMARK_DEFAULT', false),
    'engines' => [
        'implemented' => ['panorama_360', 'smart_walk'],
        'planned' => ['matterport', 'lidar', 'mesh_3d', 'gaussian_splat', 'nerf', 'photogrammetry', 'drone', 'floor_plan_3d', 'digital_twin'],
    ],
];
