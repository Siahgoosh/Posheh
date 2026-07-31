<?php

namespace Tests\Unit;

use App\Services\Settings\SystemSettingsService;
use App\Services\Sms\IpPanelSmsService;
use App\Services\Sms\SmsRelayClient;
use Mockery;
use ReflectionMethod;
use Tests\TestCase;

class OtpPatternParamsTest extends TestCase
{
    public function test_otp_pattern_values_use_named_code_key_first(): void
    {
        $settings = Mockery::mock(SystemSettingsService::class);
        $relay = Mockery::mock(SmsRelayClient::class);
        $service = new IpPanelSmsService($settings, $relay);

        $method = new ReflectionMethod(IpPanelSmsService::class, 'patternValueVariants');
        $method->setAccessible(true);

        $variants = $method->invoke($service, ['code' => '860098'], true);

        $this->assertSame('["860098"]', $variants[0]);
        $this->assertSame('{"code":"860098"}', $variants[1]);
    }
}
