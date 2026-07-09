<?php

namespace Tests\Unit;

use App\Services\Settings\SystemSettingsService;
use App\Services\Sms\IpPanelSmsService;
use Illuminate\Http\Client\Response;
use Mockery;
use ReflectionMethod;
use Tests\TestCase;

class JspdDenyResponseTest extends TestCase
{
    public function test_parse_jspd_deny_returns_actionable_message(): void
    {
        $settings = Mockery::mock(SystemSettingsService::class);
        $service = new IpPanelSmsService($settings);

        $response = new Response(new \GuzzleHttp\Psr7\Response(200, [], 'deny'));

        $method = new ReflectionMethod(IpPanelSmsService::class, 'parseJspdResponse');
        $method->setAccessible(true);

        $result = $method->invoke($service, $response);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('deny', $result['message']);
        $this->assertSame('deny', $result['details']['code']);
    }
}
