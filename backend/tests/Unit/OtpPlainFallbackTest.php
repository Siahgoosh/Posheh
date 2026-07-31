<?php

namespace Tests\Unit;

use App\Services\Settings\SystemSettingsService;
use App\Services\Sms\IpPanelSmsService;
use App\Services\Sms\SmsRelayClient;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

class OtpPlainFallbackTest extends TestCase
{
    public function test_send_otp_falls_back_to_plain_webservice_when_pattern_denied(): void
    {
        Http::fake([
            'https://ippanel.com/patterns/pattern*' => Http::response('deny', 200),
            'https://ippanel.com/services.jspd' => Http::sequence()
                ->push('deny', 200)
                ->push(json_encode(['0', '12345']), 200),
        ]);

        $settings = Mockery::mock(SystemSettingsService::class);
        $settings->shouldReceive('isSmsLive')->andReturn(true);
        $settings->shouldReceive('get')->with('sms_provider', 'maxsms')->andReturn('maxsms');
        $settings->shouldReceive('ippanelConfig')->andReturn([
            'username' => 'user',
            'password' => 'pass',
            'from_number' => '+983000505',
            'otp_from_number' => '+9810008721297974',
            'otp_pattern_code' => 'qhhly1nai3njev0',
            'api_mode' => 'jspd',
            'sms_provider' => 'maxsms',
            'base_url' => 'https://edge.ippanel.com/v1',
        ]);

        $relay = Mockery::mock(SmsRelayClient::class);
        $relay->shouldReceive('isConfigured')->andReturn(false);

        $service = new IpPanelSmsService($settings, $relay);
        $result = $service->sendOtp('09170577873', '123456');

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('otp_plain', (string) ($result['method'] ?? ''));
    }
}
