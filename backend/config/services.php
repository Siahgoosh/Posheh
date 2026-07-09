<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'zarinpal' => [
        'merchant_id' => env('ZARINPAL_MERCHANT_ID'),
        'sandbox' => env('ZARINPAL_SANDBOX', true),
    ],

    'sms' => [
        'provider' => env('SMS_PROVIDER', 'kavenegar'),
        'api_key' => env('SMS_API_KEY'),
    ],

    'ippanel' => [
        'api_key' => env('IPPANEL_API_KEY'),
        'username' => env('IPPANEL_USERNAME'),
        'password' => env('IPPANEL_PASSWORD'),
        'from_number' => env('IPPANEL_FROM_NUMBER'),
        'otp_from_number' => env('IPPANEL_OTP_FROM_NUMBER'),
        'otp_pattern_code' => env('IPPANEL_OTP_PATTERN_CODE'),
        'invite_pattern_code' => env('IPPANEL_INVITE_PATTERN_CODE'),
        'base_url' => env('IPPANEL_BASE_URL', 'https://edge.ippanel.com/v1'),
        'api_mode' => env('IPPANEL_API_MODE', 'jspd'),
    ],

];
