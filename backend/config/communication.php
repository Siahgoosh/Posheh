<?php

return [
    'visitor_session_ttl_minutes' => 30,
    'heartbeat_interval_seconds' => 25,
    'lead_scoring' => [
        'pricing_page' => 25,
        'demo_page' => 20,
        'time_on_site_5min' => 15,
        'sent_message' => 10,
        'visit_count_3' => 10,
        'staff_count_5' => 10,
        'budget_provided' => 15,
        'demo_request' => 20,
        'download' => 10,
    ],
    'iran_provinces' => [
        'تهران', 'البرز', 'اصفهان', 'فارس', 'خراسان رضوی', 'آذربایجان شرقی', 'آذربایجان غربی',
        'خوزستان', 'مازندران', 'گیلان', 'کرمان', 'یزد', 'همدان', 'قزوین', 'قم', 'گلستان',
        'اردبیل', 'بوشهر', 'چهارمحال و بختیاری', 'سیستان و بلوچستان', 'لرستان', 'مرکزی',
        'هرمزگان', 'ایلام', 'کرمانشاه', 'کردستان', 'زنجان', 'سمنان', 'خراسان شمالی',
        'خراسان جنوبی', 'کهگیلویه و بویراحمد',
    ],
    'activity_types' => [
        'real_estate_office' => 'دفتر املاک',
        'developer' => 'سازنده / سرمایه‌گذار',
        'consultant' => 'مشاور مستقل',
        'other' => 'سایر',
    ],
    'request_types' => [
        'demo' => 'درخواست دمو',
        'pricing' => 'استعلام قیمت',
        'support' => 'پشتیبانی',
        'partnership' => 'همکاری',
        'other' => 'سایر',
    ],
    'roles' => [
        'super_admin' => 'Super Admin',
        'platform_admin' => 'Manager',
        'platform_support' => 'Support Manager',
        'platform_support_agent' => 'Support Agent',
        'platform_sales' => 'Sales',
        'platform_marketing' => 'Marketing',
        'platform_viewer' => 'Viewer',
    ],
];
