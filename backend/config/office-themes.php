<?php

return [
    'modern' => [
        'id' => 'modern',
        'label' => 'مدرن (پیش‌فرض)',
        'description' => 'طراحی تمیز با گرادیان و کارت‌های شیشه‌ای',
        'defaults' => [
            'brand_color' => '#0f766e',
            'hero_style' => 'gradient',
            'card_style' => 'glass',
            'header_style' => 'sticky',
        ],
    ],
    'classic' => [
        'id' => 'classic',
        'label' => 'کلاسیک',
        'description' => 'ظاهر رسمی با هدر تیره و تایپوگرافی سنتی',
        'defaults' => [
            'brand_color' => '#1e3a5f',
            'hero_style' => 'solid',
            'card_style' => 'bordered',
            'header_style' => 'classic',
        ],
    ],
    'luxury' => [
        'id' => 'luxury',
        'label' => 'لوکس',
        'description' => 'تم طلایی-مشکی برای دفاتر لوکس',
        'defaults' => [
            'brand_color' => '#b8860b',
            'hero_style' => 'dark',
            'card_style' => 'elevated',
            'header_style' => 'minimal',
        ],
    ],
];
