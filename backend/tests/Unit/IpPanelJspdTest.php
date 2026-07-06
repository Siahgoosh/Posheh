<?php

namespace Tests\Unit;

use App\Services\Settings\SystemSettingsService;
use App\Services\Sms\IpPanelSmsService;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

class IpPanelJspdTest extends TestCase
{
    public function test_maxsms_jspd_sends_successfully(): void
    {
        $settings = Mockery::mock(SystemSettingsService::class);
        $settings->shouldReceive('ippanelConfig')->andReturn([
            'api_key' => null,
            'username' => 'paneluser',
            'password' => 'panelpass',
            'from_number' => '+9810008721297974',
            'otp_pattern_code' => null,
            'invite_pattern_code' => null,
            'base_url' => 'https://edge.ippanel.com/v1',
            'api_mode' => 'jspd',
            'sms_provider' => 'maxsms',
        ]);
        $settings->shouldReceive('get')->andReturnUsing(fn ($key, $default = null) => match ($key) {
            'ippanel_api_mode' => 'jspd',
            'sms_provider' => 'maxsms',
            default => $default,
        });

        Http::fake([
            'https://ippanel.com/services.jspd' => Http::response(['0', '12345678'], 200),
        ]);

        $result = (new IpPanelSmsService($settings))->test('09170577873', 'تست مکث');

        $this->assertTrue($result['success']);
        $this->assertSame('jspd_user', $result['method']);
    }
}
