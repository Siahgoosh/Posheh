<?php

namespace Tests\Unit;

use App\DTOs\Auth\SendOtpDTO;
use App\Jobs\SendOtpSmsJob;
use App\Models\OtpCode;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\Auth\OtpService;
use App\Services\Auth\RegistrationService;
use App\Services\Settings\SystemSettingsService;
use App\Services\Subscription\SubscriptionAccessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Mockery;
use Tests\TestCase;

class OtpWebSendTest extends TestCase
{
    use RefreshDatabase;

    public function test_send_saves_otp_and_dispatches_sms_after_response_in_live_mode(): void
    {
        Bus::fake();

        $settings = Mockery::mock(SystemSettingsService::class);
        $settings->shouldReceive('isSmsLive')->andReturn(true);

        $service = new OtpService(
            Mockery::mock(UserRepositoryInterface::class),
            $settings,
            Mockery::mock(RegistrationService::class),
            Mockery::mock(SubscriptionAccessService::class),
        );

        $result = $service->send(new SendOtpDTO(
            mobile: '09170577873',
            purpose: 'login',
        ));

        $this->assertSame('کد تأیید ارسال شد.', $result['message']);
        $this->assertDatabaseHas('otp_codes', [
            'mobile' => '09170577873',
        ]);

        Bus::assertDispatchedAfterResponse(SendOtpSmsJob::class, function (SendOtpSmsJob $job) {
            return $job->mobile === '09170577873' && strlen($job->code) === 6;
        });
    }

    public function test_send_saves_fixed_code_in_log_mode_without_dispatching_sms_job(): void
    {
        Bus::fake();

        $settings = Mockery::mock(SystemSettingsService::class);
        $settings->shouldReceive('isSmsLive')->andReturn(false);

        $service = new OtpService(
            Mockery::mock(UserRepositoryInterface::class),
            $settings,
            Mockery::mock(RegistrationService::class),
            Mockery::mock(SubscriptionAccessService::class),
        );

        $result = $service->send(new SendOtpDTO(
            mobile: '09170577873',
            purpose: 'login',
        ));

        $this->assertSame('حالت تست: کد ۱۲۳۴۵۶', $result['dev_hint']);
        $this->assertDatabaseHas('otp_codes', [
            'mobile' => '09170577873',
            'code' => '123456',
        ]);

        Bus::assertNothingDispatched();
    }
}
