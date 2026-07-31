<?php

namespace Tests\Unit;

use App\DTOs\Auth\SendOtpDTO;
use App\Models\OtpCode;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\Auth\OtpService;
use App\Services\Auth\RegistrationService;
use App\Services\Settings\SystemSettingsService;
use App\Services\Sms\IpPanelSmsService;
use App\Services\Subscription\SubscriptionAccessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class OtpWebSendTest extends TestCase
{
    use RefreshDatabase;

    public function test_send_saves_otp_after_successful_sms_in_live_mode(): void
    {
        $settings = Mockery::mock(SystemSettingsService::class);
        $settings->shouldReceive('isSmsLive')->andReturn(true);

        $sms = Mockery::mock(IpPanelSmsService::class);
        $sms->shouldReceive('sendOtp')
            ->once()
            ->with('09170577873', Mockery::type('string'))
            ->andReturn(['success' => true, 'method' => 'jspd_otp_panel']);

        $service = $this->makeService($settings, $sms);

        $result = $service->send(new SendOtpDTO(
            mobile: '09170577873',
            purpose: 'login',
        ));

        $this->assertSame('کد تأیید ارسال شد.', $result['message']);
        $this->assertTrue($result['sms_sent']);
        $this->assertDatabaseHas('otp_codes', [
            'mobile' => '09170577873',
        ]);
    }

    public function test_send_does_not_save_otp_when_sms_fails_in_live_mode(): void
    {
        $settings = Mockery::mock(SystemSettingsService::class);
        $settings->shouldReceive('isSmsLive')->andReturn(true);

        $sms = Mockery::mock(IpPanelSmsService::class);
        $sms->shouldReceive('sendOtp')
            ->once()
            ->andReturn(['success' => false, 'message' => 'خطای تست']);

        $service = $this->makeService($settings, $sms);

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        $service->send(new SendOtpDTO(
            mobile: '09170577873',
            purpose: 'login',
        ));

        $this->assertDatabaseMissing('otp_codes', [
            'mobile' => '09170577873',
        ]);
    }

    public function test_send_saves_fixed_code_in_log_mode_without_real_sms(): void
    {
        $settings = Mockery::mock(SystemSettingsService::class);
        $settings->shouldReceive('isSmsLive')->andReturn(false);

        $sms = Mockery::mock(IpPanelSmsService::class);
        $sms->shouldReceive('sendOtp')
            ->once()
            ->andReturn(['success' => true, 'method' => 'log']);

        $service = $this->makeService($settings, $sms);

        $result = $service->send(new SendOtpDTO(
            mobile: '09170577873',
            purpose: 'login',
        ));

        $this->assertTrue($result['sms_sent']);
        $this->assertDatabaseHas('otp_codes', [
            'mobile' => '09170577873',
            'code' => '123456',
        ]);
    }

    private function makeService(SystemSettingsService $settings, IpPanelSmsService $sms): OtpService
    {
        return new OtpService(
            Mockery::mock(UserRepositoryInterface::class),
            $settings,
            $sms,
            Mockery::mock(RegistrationService::class),
            Mockery::mock(SubscriptionAccessService::class),
        );
    }
}
