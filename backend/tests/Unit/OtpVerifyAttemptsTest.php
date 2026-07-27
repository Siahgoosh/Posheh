<?php

namespace Tests\Unit;

use App\DTOs\Auth\VerifyOtpDTO;
use App\Enums\UserRole;
use App\Models\Office;
use App\Models\OtpCode;
use App\Models\User;
use App\Repositories\Eloquent\UserRepository;
use App\Services\Auth\OtpService;
use App\Services\Auth\RegistrationService;
use App\Services\Subscription\SubscriptionAccessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

class OtpVerifyAttemptsTest extends TestCase
{
    use RefreshDatabase;

    public function test_verify_accepts_correct_code_after_five_wrong_attempts(): void
    {
        $office = Office::create(['name' => 'Test Office', 'slug' => 'otp-office']);
        User::create([
            'name' => 'Test User',
            'mobile' => '09170577873',
            'office_id' => $office->id,
            'role' => UserRole::Consultant,
            'is_active' => true,
        ]);

        OtpCode::create([
            'mobile' => '09170577873',
            'code' => '150955',
            'purpose' => 'login',
            'attempts' => 5,
            'expires_at' => now()->addMinutes(5),
        ]);

        $service = $this->makeService();

        $result = $service->verify(new VerifyOtpDTO(
            mobile: '09170577873',
            code: '150955',
        ));

        $this->assertSame('09170577873', $result['user']->mobile);
        $this->assertNotEmpty($result['token']);
    }

    public function test_verify_accepts_matching_code_from_older_active_row(): void
    {
        $office = Office::create(['name' => 'Older OTP Office', 'slug' => 'older-otp-office']);
        User::create([
            'name' => 'Older OTP User',
            'mobile' => '09170577873',
            'office_id' => $office->id,
            'role' => UserRole::Consultant,
            'is_active' => true,
        ]);

        OtpCode::create([
            'mobile' => '9170577873',
            'code' => '150955',
            'purpose' => 'login',
            'expires_at' => now()->addMinutes(5),
        ]);

        OtpCode::create([
            'mobile' => '09170577873',
            'code' => '999999',
            'purpose' => 'login',
            'expires_at' => now()->addMinutes(5),
        ]);

        $service = $this->makeService();

        $result = $service->verify(new VerifyOtpDTO(
            mobile: '09170577873',
            code: '150955',
        ));

        $this->assertSame('09170577873', $result['user']->mobile);
    }

    {
        $office = Office::create(['name' => 'Cache Office', 'slug' => 'cache-office']);
        User::create([
            'name' => 'Cache User',
            'mobile' => '09121111111',
            'office_id' => $office->id,
            'role' => UserRole::Consultant,
            'is_active' => true,
        ]);

        Cache::put('otp_active:09121111111', '150955', now()->addMinutes(5));

        $service = $this->makeService();

        $result = $service->verify(new VerifyOtpDTO(
            mobile: '09121111111',
            code: '150955',
        ));

        $this->assertSame('09121111111', $result['user']->mobile);
        $this->assertNull(Cache::get('otp_active:09121111111'));
    }

    private function makeService(): OtpService
    {
        return new OtpService(
            new UserRepository,
            Mockery::mock(SystemSettingsService::class),
            Mockery::mock(RegistrationService::class),
            Mockery::mock(SubscriptionAccessService::class),
        );
    }
}
