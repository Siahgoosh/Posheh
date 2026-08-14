<?php

return [
    'timezone' => 'Asia/Tehran',
    'feature_key' => 'content_planner',
    'sms_template' => env(
        'CONTENT_PLANNER_SMS_TEMPLATE',
        "پوشه | یادآوری انتشار\nزمان انتشار محتوای شما رسیده است.\nعنوان: {title}\nزمان: {jalali_date} - {time}"
    ),
    'max_sms_attempts' => 3,
    'reminder_offsets' => [
        0 => 'هنگام زمان انتشار',
        15 => '۱۵ دقیقه قبل',
        30 => '۳۰ دقیقه قبل',
        60 => '۱ ساعت قبل',
        120 => '۲ ساعت قبل',
        1440 => '۱ روز قبل',
    ],
    'content_types' => [
        'post' => 'پست',
        'reels' => 'ریلز',
        'story' => 'استوری',
        'carousel' => 'کاروسل',
        'video' => 'ویدئو',
        'photo' => 'عکس',
        'article' => 'مقاله',
        'other' => 'سایر',
    ],
    'platforms' => [
        'instagram' => 'اینستاگرام',
        'telegram' => 'تلگرام',
        'whatsapp' => 'واتساپ',
        'other' => 'سایر',
    ],
    'goals' => [
        'lead' => 'جذب مشتری',
        'sale' => 'فروش',
        'branding' => 'برندسازی',
        'education' => 'آموزش',
        'trust' => 'اعتمادسازی',
        'engagement' => 'تعامل',
        'product' => 'معرفی محصول',
        'listing' => 'معرفی فایل',
        'entertainment' => 'سرگرمی',
        'announcement' => 'اطلاع‌رسانی',
        'other' => 'سایر',
    ],
    'statuses' => [
        'draft' => 'پیش‌نویس',
        'scheduled' => 'زمان‌بندی‌شده',
        'reminder_sent' => 'یادآوری ارسال شد',
        'published' => 'منتشرشده',
        'cancelled' => 'لغوشده',
        'failed' => 'خطا',
    ],
    'media_mimes' => ['jpg', 'jpeg', 'png', 'webp', 'mp4'],
    'media_max_kb' => 51200,
];
