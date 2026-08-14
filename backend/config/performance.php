<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Performance Budget (configuration-based — not field/CrUX data)
    |--------------------------------------------------------------------------
    | Lab targets only. UNKNOWN when field data unavailable. Never treat as Google score.
    */
    'budget' => [
        'page_weight_kb' => (int) env('PERF_BUDGET_PAGE_KB', 1800),
        'js_kb' => (int) env('PERF_BUDGET_JS_KB', 450),
        'css_kb' => (int) env('PERF_BUDGET_CSS_KB', 120),
        'image_kb' => (int) env('PERF_BUDGET_IMAGE_KB', 800),
        'requests' => (int) env('PERF_BUDGET_REQUESTS', 80),
        'ttfb_ms' => (int) env('PERF_BUDGET_TTFB_MS', 800),
        'lcp_ms' => (int) env('PERF_BUDGET_LCP_MS', 2500),
        'inp_ms' => (int) env('PERF_BUDGET_INP_MS', 200),
        'cls' => (float) env('PERF_BUDGET_CLS', 0.1),
    ],

    'url_policy' => [
        'preferred_scheme' => 'https',
        'preferred_host' => env('PERF_PREFERRED_HOST', 'posheapp.ir'),
        'trailing_slash' => 'omit', // omit | keep
        'strip_tracking_params' => ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'fbclid', 'gclid'],
    ],

    'security_headers' => [
        'enabled' => (bool) env('SECURITY_HEADERS_ENABLED', true),
        'hsts' => (bool) env('SECURITY_HEADERS_HSTS', true),
        'hsts_max_age' => (int) env('SECURITY_HEADERS_HSTS_MAX_AGE', 31536000),
        // CSP kept off by default — enable only after staging test (Phase 8 rule)
        'csp_enabled' => (bool) env('SECURITY_HEADERS_CSP', false),
        'csp' => env('SECURITY_HEADERS_CSP_POLICY', "default-src 'self'; img-src 'self' data: https:; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; connect-src 'self' https:; frame-ancestors 'self'; base-uri 'self'; form-action 'self'"),
    ],
];
