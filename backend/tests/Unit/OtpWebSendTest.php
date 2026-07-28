<?php

namespace Tests\Unit;

use App\DTOs\Auth\SendOtpDTO;
use App\Models\OtpCode;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\Auth\OtpService;
use App\Services\Auth\RegistrationService;
use App\Services\Settings\SystemSettingsService;
use App\Services\Sms\OtpSmsDispatcher;
use App\Services\Subscription\SubscriptionAccessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class OtpWebSendTest extends TestCase
{
    use RefreshDatabase;

    public function test_send_saves_otp_and_dispatches_sms_in_live_mode(): void
    {
        $settings = Mockery::mock(SystemSettingsService::class);
        $settings->shouldReceive('isSmsLive')->andReturn(true);

        $dispatcher = Mockery::mock(OtpSmsDispatcher::class);
        $dispatcher->shouldReceive('dispatch')
            ->once()
            ->with('09170577873', Mockery::type('string'));

        $service = $this->makeService($settings, $dispatcher);

        $result = $service->send(new SendOtpDTO(
            mobile: '09170577873',
            purpose: 'login',
        ));

        $this->assertSame('کد تأیید ارسال شد.', $result['message']);
        $this->assertDatabaseHas('otp_codes', [
            'mobile' => '09170577873',
        ]);
    }

    public function test_send_saves_fixed_code_in_log_mode_without_dispatching_sms(): void
    {
        $settings = Mockery::mock(SystemSettingsService::class);
        $settings->shouldReceive('isSmsLive')->andReturn(false);

        $dispatcher = Mockery::mock(OtpSmsDispatcher::class);
        $dispatcher->shouldNotReceive('dispatch');

        $service = $this->makeService($settings, $dispatcher);

        $result = $service->send(new SendOtpDTO(
            mobile: '09170577873',
            purpose: 'login',
        ));

        $this->assertSame('حالت تست: کد ۱۲۳۴۵۶', $result['dev_hint']);
        $this->assertDatabaseHas('otp_codes', [
            'mobile' => '09170577873',
            'code' => '123456',
        ]);
    }

    private function makeService(SystemSettingsService $settings, OtpSmsDispatcher $dispatcher): OtpService
    {
        return new OtpService(
            Mockery::mock(UserRepositoryInterface::class),
            $settings,
            Mockery::mock(RegistrationService::class),
            Mockery::mock(SubscriptionAccessService::class),
            $dispatcher,
        );
    }
}
