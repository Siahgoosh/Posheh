<?php

return [
    'default_office_id' => env('CRO_CRM_SYNC_OFFICE_ID') ? (int) env('CRO_CRM_SYNC_OFFICE_ID') : null,
    'notify_telegram' => (bool) env('CRO_NOTIFY_TELEGRAM', true),
    'high_score_alert' => (int) env('CRO_HIGH_SCORE_ALERT', 70),
    'form' => [
        'rate_limit_per_ip' => (int) env('CRO_LEAD_RATE_LIMIT', 5),
        'honeypot_field' => 'website',
        'min_phone_digits' => 10,
    ],
    'ab_test' => [
        'min_sample_per_variant' => (int) env('CRO_AB_MIN_SAMPLE', 200),
        'min_days' => (int) env('CRO_AB_MIN_DAYS', 14),
    ],
    'sticky_cta_enabled' => (bool) env('CRO_STICKY_CTA', true),
    'exit_intent_enabled' => (bool) env('CRO_EXIT_INTENT', false), // desktop only if enabled
];
