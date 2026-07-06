<?php

namespace Tests\Unit;

use App\Services\Settings\SystemSettingsService;
use App\Services\Sms\IpPanelSmsService;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

class IpPanelOtpTest extends TestCase
{
    public function test_otp_uses_jspd_pattern_when_configured(): void
    {
        $settings = Mockery::mock(SystemSettingsService::class);
        $settings->shouldReceive('isSmsLive')->andReturn(true);
        $settings->shouldReceive('ippanelConfig')->andReturn([
            'api_key' => null,
            'username' => 'paneluser',
            'password' => 'panelpass',
            'from_number' => '+9810008721297974',
            'otp_pattern_code' => 'qhhly1nai3njev0',
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
            'https://ippanel.com/services.jspd' => Http::response(['0', '99887766'], 200),
        ]);

        $sent = (new IpPanelSmsService($settings))->sendOtp('09170577873', '554433');

        $this->assertTrue($sent);
        Http::assertSent(function ($request) {
            if ($request->url() !== 'https://ippanel.com/services.jspd') {
                return false;
            }
            $data = $request->data();

            return ($data['op'] ?? null) === 'pattern'
                && ($data['p_code'] ?? null) === 'qhhly1nai3njev0'
                && str_contains($data['p_values'] ?? '', '554433');
        });
    }
}
