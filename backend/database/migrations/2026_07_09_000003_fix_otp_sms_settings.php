<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $this->upsertSetting('ippanel_otp_pattern_code', 'qhhly1nai3njev0');
        $this->upsertSetting('ippanel_otp_from_number', '+9810008721297974');
        $this->upsertSetting('sms_mode', 'live');

        $provider = DB::table('system_settings')->where('key', 'sms_provider')->value('value');
        if ($provider === null || $provider === '' || str_contains((string) $provider, '=')) {
            $this->upsertSetting('sms_provider', 'maxsms');
        }
    }

    private function upsertSetting(string $key, string $value): void
    {
        $updated = DB::table('system_settings')->where('key', $key)->update([
            'value' => $value,
            'updated_at' => now(),
        ]);

        if ($updated === 0 && ! DB::table('system_settings')->where('key', $key)->exists()) {
            DB::table('system_settings')->insert([
                'group' => 'sms',
                'key' => $key,
                'value' => $value,
                'label' => $key,
                'type' => 'text',
                'is_secret' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // keep production OTP settings
    }
};
