<?php

namespace Tests\Unit;

use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\Auth\OtpService;
use App\Services\Auth\RegistrationService;
use App\Services\Settings\SystemSettingsService;
use App\Services\Sms\IpPanelSmsService;
use Mockery;
use ReflectionMethod;
use Tests\TestCase;

class OtpCodeMatchTest extends TestCase
{
    public function test_codes_match_handles_leading_zero_drift(): void
    {
        $service = new OtpService(
            Mockery::mock(UserRepositoryInterface::class),
            Mockery::mock(SystemSettingsService::class),
            Mockery::mock(IpPanelSmsService::class),
            Mockery::mock(RegistrationService::class),
            Mockery::mock(SubscriptionAccessService::class),
        );

        $method = new ReflectionMethod(OtpService::class, 'codesMatch');
        $method->setAccessible(true);

        $this->assertTrue($method->invoke($service, '037526', '37526'));
        $this->assertTrue($method->invoke($service, '123456', '123456'));
        $this->assertFalse($method->invoke($service, '123456', '654321'));
    }

    public function test_generated_otp_codes_never_start_with_zero(): void
    {
        $settings = Mockery::mock(SystemSettingsService::class);
        $settings->shouldReceive('isSmsLive')->andReturn(true);

        $service = new OtpService(
            Mockery::mock(UserRepositoryInterface::class),
            $settings,
            Mockery::mock(IpPanelSmsService::class),
            Mockery::mock(RegistrationService::class),
            Mockery::mock(SubscriptionAccessService::class),
        );

        $method = new ReflectionMethod(OtpService::class, 'generateOtpCode');
        $method->setAccessible(true);

        for ($i = 0; $i < 50; $i++) {
            $code = (string) $method->invoke($service);
            $this->assertSame(6, strlen($code));
            $this->assertNotSame('0', $code[0]);
        }
    }
}
