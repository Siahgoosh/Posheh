<?php

namespace Tests\Feature;

use App\Enums\PropertyPermission;
use App\Enums\PropertyType;
use App\Enums\UserRole;
use App\Models\Office;
use App\Models\OtpCode;
use App\Models\Property;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_request_otp(): void
    {
        $user = User::factory()->create(['mobile' => '09121111111']);

        $response = $this->postJson('/api/v1/auth/otp/send', [
            'mobile' => '09121111111',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['message', 'expires_in']);

        $this->assertDatabaseHas('otp_codes', ['mobile' => '09121111111']);
    }

    public function test_otp_send_is_rate_limited(): void
    {
        User::factory()->create(['mobile' => '09121111111']);

        OtpCode::create([
            'mobile' => '09121111111',
            'code' => '123456',
            'expires_at' => now()->addMinutes(5),
        ]);

        $response = $this->postJson('/api/v1/auth/otp/send', [
            'mobile' => '09121111111',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['mobile']);
    }

    public function test_user_can_verify_otp_and_login(): void
    {
        $office = Office::create(['name' => 'Test Office', 'slug' => 'test-office']);
        $user = User::create([
            'name' => 'Test User',
            'mobile' => '09121111111',
            'office_id' => $office->id,
            'role' => UserRole::Consultant,
            'is_active' => true,
        ]);

        OtpCode::create([
            'mobile' => '09121111111',
            'code' => '123456',
            'expires_at' => now()->addMinutes(5),
        ]);

        $response = $this->postJson('/api/v1/auth/otp/verify', [
            'mobile' => '09121111111',
            'code' => '123456',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['user', 'token', 'token_type']);
    }
}
