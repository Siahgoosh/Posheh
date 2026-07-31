<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Seeder;

class SystemSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            ['group' => 'sms', 'key' => 'sms_mode', 'value' => env('SMS_MODE', app()->environment('production') ? 'live' : 'log'), 'label' => 'حالت پیامک (log یا live)', 'type' => 'select'],
            ['group' => 'sms', 'key' => 'ippanel_api_key', 'value' => env('IPPANEL_API_KEY', ''), 'label' => 'کلید API پنل IPPanel', 'type' => 'password', 'is_secret' => true],
            ['group' => 'sms', 'key' => 'ippanel_username', 'value' => env('IPPANEL_USERNAME', ''), 'label' => 'نام کاربری IPPanel (جایگزین API Key)', 'type' => 'text'],
            ['group' => 'sms', 'key' => 'ippanel_password', 'value' => env('IPPANEL_PASSWORD', ''), 'label' => 'رمز عبور IPPanel', 'type' => 'password', 'is_secret' => true],
            ['group' => 'sms', 'key' => 'ippanel_from_number', 'value' => env('IPPANEL_FROM_NUMBER', ''), 'label' => 'شماره ارسال‌کننده (مثال: +983000505)', 'type' => 'text'],
            ['group' => 'sms', 'key' => 'ippanel_otp_from_number', 'value' => env('IPPANEL_OTP_FROM_NUMBER', ''), 'label' => 'خط پترن OTP (مثال: +9810008721297974)', 'type' => 'text'],
            ['group' => 'sms', 'key' => 'ippanel_otp_pattern_code', 'value' => env('IPPANEL_OTP_PATTERN_CODE', 'qhhly1nai3njev0'), 'label' => 'کد پترن OTP', 'type' => 'text'],
            ['group' => 'sms', 'key' => 'ippanel_invite_pattern_code', 'value' => env('IPPANEL_INVITE_PATTERN_CODE', ''), 'label' => 'کد پترن دعوت مشاور', 'type' => 'text'],
            ['group' => 'sms', 'key' => 'ippanel_base_url', 'value' => env('IPPANEL_BASE_URL', 'https://edge.ippanel.com/v1'), 'label' => 'آدرس API', 'type' => 'text'],
            ['group' => 'sms', 'key' => 'sms_provider', 'value' => env('SMS_PROVIDER', 'maxsms'), 'label' => 'ارائه‌دهنده پیامک (maxsms یا ippanel)', 'type' => 'select'],
            ['group' => 'sms', 'key' => 'ippanel_api_mode', 'value' => env('IPPANEL_API_MODE', 'auto'), 'label' => 'روش API (auto/jspd/edge/legacy)', 'type' => 'select'],
            ['group' => 'payment', 'key' => 'aqayepardakht_pin', 'value' => env('AQAYEPARDAKHT_PIN', ''), 'label' => 'پین آقای پرداخت', 'type' => 'password', 'is_secret' => true],
            ['group' => 'payment', 'key' => 'aqayepardakht_sandbox', 'value' => env('AQAYEPARDAKHT_SANDBOX', '1') ? '1' : '0', 'label' => 'حالت تست آقای پرداخت', 'type' => 'boolean'],
            ['group' => 'payment', 'key' => 'zibal_merchant', 'value' => env('ZIBAL_MERCHANT', ''), 'label' => 'مرچنت زیبال', 'type' => 'text', 'is_secret' => true],
            ['group' => 'payment', 'key' => 'zibal_sandbox', 'value' => env('ZIBAL_SANDBOX', '0') ? '1' : '0', 'label' => 'حالت تست زیبال', 'type' => 'boolean'],
            ['group' => 'general', 'key' => 'app_public_name', 'value' => 'پوشه', 'label' => 'نام نرم‌افزار', 'type' => 'text'],
            ['group' => 'general', 'key' => 'trial_hours_solo', 'value' => '48', 'label' => 'ساعت دوره آزمایشی پنل فردی', 'type' => 'number'],
            ['group' => 'general', 'key' => 'frontend_url', 'value' => env('FRONTEND_URL', env('APP_URL', 'http://localhost:8000')), 'label' => 'آدرس فرانت‌اند', 'type' => 'text'],
            ['group' => 'general', 'key' => 'invite_sms_template', 'value' => 'شما به دفتر {office} در پوشه دعوت شدید. با شماره موبایل خود وارد شوید.', 'label' => 'متن پیامک دعوت', 'type' => 'textarea'],
        ];

        foreach ($settings as $setting) {
            $existing = SystemSetting::where('key', $setting['key'])->first();

            if ($existing) {
                $existing->update([
                    'group' => $setting['group'],
                    'label' => $setting['label'],
                    'type' => $setting['type'],
                    'is_secret' => $setting['is_secret'] ?? false,
                ]);

                continue;
            }

            SystemSetting::create($setting);
        }
    }
}
