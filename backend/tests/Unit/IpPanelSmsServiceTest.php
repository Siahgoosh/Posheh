<?php

namespace Tests\Unit;

use App\Services\Settings\SystemSettingsService;
use App\Services\Sms\IpPanelSmsService;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

class IpPanelSmsServiceTest extends TestCase
{
    public function test_falls_back_to_legacy_get_when_edge_post_returns_502(): void
    {
        $settings = Mockery::mock(SystemSettingsService::class);
        $settings->shouldReceive('ippanelConfig')->andReturn([
            'api_key' => 'test-api-key',
            'username' => null,
            'password' => null,
            'from_number' => '+983000505',
            'otp_pattern_code' => null,
            'invite_pattern_code' => null,
            'base_url' => 'https://edge.ippanel.com/v1',
            'api_mode' => 'auto',
        ]);
        $settings->shouldReceive('get')->with('ippanel_api_mode', 'auto')->andReturn('auto');

        Http::fake([
            'https://edge.ippanel.com/v1/api/send' => Http::response('<html>502</html>', 502),
            'https://edge.ippanel.com/v1/api/send/webservice*' => Http::response([
                'status' => true,
                'message' => 'ارسال شد',
            ], 200),
            'https://edge.ippanel.com/api/send/webservice*' => Http::response([
                'status' => true,
                'message' => 'ارسال شد',
            ], 200),
        ]);

        $service = new IpPanelSmsService($settings);
        $result = $service->test('09170577873', 'تست');

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('legacy_get', $result['method'] ?? '');
    }
}
