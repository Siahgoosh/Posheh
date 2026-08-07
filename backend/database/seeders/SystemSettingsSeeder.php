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
            ['group' => 'communication', 'key' => 'comm_telegram_bot_token', 'value' => env('TELEGRAM_PLATFORM_BOT_TOKEN', ''), 'label' => 'توکن ربات تلگرام پلتفرم (Communication Center)', 'type' => 'password', 'is_secret' => true],
            ['group' => 'communication', 'key' => 'comm_telegram_alert_chat_ids', 'value' => env('TELEGRAM_PLATFORM_ALERT_CHAT_IDS', ''), 'label' => 'چت‌آیدی اعلان مدیر (با کاما جدا کنید)', 'type' => 'text'],
            ['group' => 'communication', 'key' => 'comm_whatsapp_phone_number_id', 'value' => env('WHATSAPP_PHONE_NUMBER_ID', ''), 'label' => 'WhatsApp Phone Number ID', 'type' => 'text'],
            ['group' => 'communication', 'key' => 'comm_whatsapp_access_token', 'value' => env('WHATSAPP_ACCESS_TOKEN', ''), 'label' => 'WhatsApp Access Token', 'type' => 'password', 'is_secret' => true],
            ['group' => 'communication', 'key' => 'comm_email_from', 'value' => env('COMM_EMAIL_FROM', env('MAIL_FROM_ADDRESS', '')), 'label' => 'ایمیل ارسال پشتیبانی', 'type' => 'text'],
            ['group' => 'communication', 'key' => 'comm_email_from_name', 'value' => env('COMM_EMAIL_FROM_NAME', 'پشتیبانی پوشه'), 'label' => 'نام ارسال‌کننده ایمیل', 'type' => 'text'],
            ['group' => 'communication', 'key' => 'comm_email_inbound_domain', 'value' => env('COMM_EMAIL_INBOUND_DOMAIN', 'support.posheapp.ir'), 'label' => 'دامنه inbound ایمیل تیکت', 'type' => 'text'],
            ['group' => 'communication', 'key' => 'comm_email_webhook_secret', 'value' => env('COMM_EMAIL_WEBHOOK_SECRET', ''), 'label' => 'Secret webhook ایمیل inbound', 'type' => 'password', 'is_secret' => true],
            ['group' => 'communication', 'key' => 'comm_ai_provider', 'value' => env('COMM_AI_PROVIDER', 'internal'), 'label' => 'ارائه‌دهنده AI (internal یا openai)', 'type' => 'select'],
            ['group' => 'communication', 'key' => 'comm_ai_openai_key', 'value' => env('OPENAI_API_KEY', ''), 'label' => 'کلید OpenAI', 'type' => 'password', 'is_secret' => true],
            ['group' => 'communication', 'key' => 'comm_ai_openai_model', 'value' => env('COMM_AI_OPENAI_MODEL', 'gpt-4o-mini'), 'label' => 'مدل OpenAI', 'type' => 'text'],
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
