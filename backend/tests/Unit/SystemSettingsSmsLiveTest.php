<?php

namespace Tests\Unit;

use App\Models\SystemSetting;
use App\Services\Settings\SystemSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SystemSettingsSmsLiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_production_sends_live_when_credentials_configured_even_if_db_says_log(): void
    {
        $this->app['env'] = 'production';

        SystemSetting::create([
            'group' => 'sms', 'key' => 'sms_mode', 'value' => 'log',
            'label' => 'mode', 'type' => 'select',
        ]);
        SystemSetting::create([
            'group' => 'sms', 'key' => 'ippanel_username', 'value' => 'user',
            'label' => 'user', 'type' => 'text',
        ]);
        SystemSetting::create([
            'group' => 'sms', 'key' => 'ippanel_password', 'value' => 'pass',
            'label' => 'pass', 'type' => 'password', 'is_secret' => true,
        ]);
        SystemSetting::create([
            'group' => 'sms', 'key' => 'ippanel_from_number', 'value' => '+9810008721297974',
            'label' => 'from', 'type' => 'text',
        ]);

        $service = app(SystemSettingsService::class);

        $this->assertTrue($service->isSmsLive());
    }
}
