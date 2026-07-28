<?php

namespace Tests\Unit;

use App\Models\OtpCode;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\Auth\OtpService;
use App\Services\Auth\RegistrationService;
use App\Services\Settings\SystemSettingsService;
use App\Services\Sms\OtpSmsDispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Mockery;
use Tests\TestCase;

class OtpVerifyTest extends TestCase
{
    use RefreshDatabase;

    public function test_verify_rejects_wrong_code_for_latest_otp(): void
    {
        OtpCode::create([
            'mobile' => '09121111111',
            'code' => '111111',
            'purpose' => 'login',
            'expires_at' => now()->addMinutes(5),
        ]);

        $settings = Mockery::mock(SystemSettingsService::class);
        $users = Mockery::mock(UserRepositoryInterface::class);

        $service = new OtpService(
            $users,
            $settings,
            Mockery::mock(RegistrationService::class),
            Mockery::mock(SubscriptionAccessService::class),
            Mockery::mock(OtpSmsDispatcher::class),
        );

        $this->expectException(ValidationException::class);

        $service->verify(new \App\DTOs\Auth\VerifyOtpDTO(
            mobile: '09121111111',
            code: '222222',
        ));
    }
}
