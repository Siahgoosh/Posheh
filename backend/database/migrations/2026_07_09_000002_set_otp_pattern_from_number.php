<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $existing = DB::table('system_settings')->where('key', 'ippanel_otp_from_number')->first();

        if (! $existing) {
            DB::table('system_settings')->insert([
                'group' => 'sms',
                'key' => 'ippanel_otp_from_number',
                'value' => '+9810008721297974',
                'label' => 'خط پترن OTP (مثال: +9810008721297974)',
                'type' => 'text',
                'is_secret' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return;
        }

        if (empty($existing->value)) {
            DB::table('system_settings')->where('key', 'ippanel_otp_from_number')->update([
                'value' => '+9810008721297974',
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // keep setting
    }
};
