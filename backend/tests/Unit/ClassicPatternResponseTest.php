<?php

namespace Tests\Unit;

use App\Services\Settings\SystemSettingsService;
use App\Services\Sms\IpPanelSmsService;
use Illuminate\Http\Client\Response;
use Mockery;
use ReflectionMethod;
use Tests\TestCase;

class ClassicPatternResponseTest extends TestCase
{
    public function test_classic_pattern_error_code_is_not_treated_as_success(): void
    {
        $settings = Mockery::mock(SystemSettingsService::class);
        $service = new IpPanelSmsService($settings);

        $response = new Response(new \GuzzleHttp\Psr7\Response(200, [], '["962","the username or password is incorrect"]'));

        $method = new ReflectionMethod(IpPanelSmsService::class, 'parseClassicPatternResponse');
        $method->setAccessible(true);

        $result = $method->invoke($service, $response);

        $this->assertFalse($result['success']);
        $this->assertSame('962', $result['details']['code']);
    }

    public function test_classic_pattern_success_codes(): void
    {
        $settings = Mockery::mock(SystemSettingsService::class);
        $service = new IpPanelSmsService($settings);

        $response = new Response(new \GuzzleHttp\Psr7\Response(200, [], '["0","123456789"]'));

        $method = new ReflectionMethod(IpPanelSmsService::class, 'parseClassicPatternResponse');
        $method->setAccessible(true);

        $result = $method->invoke($service, $response);

        $this->assertTrue($result['success']);
    }

    public function test_classic_pattern_numeric_tracking_is_success(): void
    {
        $settings = Mockery::mock(SystemSettingsService::class);
        $service = new IpPanelSmsService($settings);

        $response = new Response(new \GuzzleHttp\Psr7\Response(200, [], '1441216818'));

        $method = new ReflectionMethod(IpPanelSmsService::class, 'parseClassicPatternResponse');
        $method->setAccessible(true);

        $result = $method->invoke($service, $response);

        $this->assertTrue($result['success']);
        $this->assertSame('1441216818', $result['details']['tracking']);
    }
}
