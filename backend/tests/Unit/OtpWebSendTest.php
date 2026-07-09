<?php

namespace Tests\Unit;

use App\Services\Auth\OtpService;
use App\Services\Settings\SystemSettingsService;
use App\Services\Sms\IpPanelSmsService;
use Mockery;
use ReflectionMethod;
use Tests\TestCase;

class OtpWebSendTest extends TestCase
{
    public function test_web_otp_path_calls_sms_service_directly(): void
    {
        $settings = Mockery::mock(SystemSettingsService::class);
        $settings->shouldReceive('isSmsLive')->andReturn(true);
        $settings->shouldReceive('get')->andReturn('live');

        $sms = Mockery::mock(IpPanelSmsService::class);
        $sms->shouldReceive('sendOtp')
            ->once()
            ->with('09170577873', Mockery::type('string'))
            ->andReturn(['success' => true, 'method' => 'classic_otp_f9810008721297974']);

        $service = new OtpService(
            Mockery::mock(\App\Repositories\Contracts\UserRepositoryInterface::class),
            $settings,
            $sms,
        );

        $method = new ReflectionMethod(OtpService::class, 'dispatchOtpSms');
        $method->setAccessible(true);

        $result = $method->invoke($service, '09170577873', '123456');

        $this->assertTrue($result['success']);
        $this->assertSame('classic_otp_f9810008721297974', $result['method']);
    }
}
