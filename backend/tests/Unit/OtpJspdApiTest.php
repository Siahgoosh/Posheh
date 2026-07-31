<?php

namespace Tests\Unit;

use App\Services\Settings\SystemSettingsService;
use App\Services\Sms\IpPanelSmsService;
use App\Services\Sms\SmsRelayClient;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

class OtpJspdApiTest extends TestCase
{
    public function test_jspd_mode_sends_otp_only_via_plain_webservice_api(): void
    {
        Http::fake([
            'https://ippanel.com/services.jspd' => Http::response(json_encode(['0', '12345']), 200),
        ]);

        $settings = Mockery::mock(SystemSettingsService::class);
        $settings->shouldReceive('isSmsLive')->andReturn(true);
        $settings->shouldReceive('get')->with('sms_provider', 'maxsms')->andReturn('maxsms');
        $settings->shouldReceive('ippanelConfig')->andReturn([
            'username' => 'user',
            'password' => 'pass',
            'api_key' => 'test-api-key',
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
        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => $request->url() === 'https://ippanel.com/services.jspd'
            && ($request->data()['op'] ?? '') === 'send');
    }

    public function test_otp_uses_webservice_only_even_in_edge_mode(): void
    {
        Http::fake([
            'https://edge.ippanel.com/*' => Http::response(['meta' => ['status' => true]], 200),
            'https://ippanel.com/services.jspd' => Http::response(json_encode(['0', 'ok']), 200),
        ]);

        $settings = Mockery::mock(SystemSettingsService::class);
        $settings->shouldReceive('isSmsLive')->andReturn(true);
        $settings->shouldReceive('get')->with('sms_provider', 'maxsms')->andReturn('maxsms');
        $settings->shouldReceive('ippanelConfig')->andReturn([
            'username' => 'user',
            'password' => 'pass',
            'api_key' => 'test-api-key',
            'from_number' => '+983000505',
            'api_mode' => 'edge',
            'sms_provider' => 'maxsms',
        ]);

        $relay = Mockery::mock(SmsRelayClient::class);
        $relay->shouldReceive('isConfigured')->andReturn(false);

        $service = new IpPanelSmsService($settings, $relay);
        $result = $service->sendOtp('09170577873', '654321');

        $this->assertTrue($result['success']);
        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'edge.ippanel.com'));
        Http::assertSent(fn ($request) => ($request->data()['op'] ?? '') === 'send');
    }
}
