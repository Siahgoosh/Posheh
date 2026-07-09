<?php

namespace Tests\Feature;

use App\Models\Office;
use App\Models\OtpCode;
use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OtpCsrfTest extends TestCase
{
    use RefreshDatabase;

    public function test_verify_accepts_browser_origin_without_csrf_token(): void
    {
        $office = Office::create(['name' => 'CSRF Office', 'slug' => 'csrf-office']);
        User::create([
            'name' => 'CSRF User',
            'mobile' => '09170577873',
            'office_id' => $office->id,
            'role' => UserRole::Consultant,
            'is_active' => true,
        ]);

        OtpCode::create([
            'mobile' => '09170577873',
            'code' => '376967',
            'expires_at' => now()->addMinutes(5),
        ]);

        $response = $this->postJson('/api/v1/auth/otp/verify', [
            'mobile' => '09170577873',
            'code' => '376967',
            'device_id' => 'browser-device',
            'device_name' => 'Chrome',
            'platform' => 'web',
        ], [
            'Origin' => 'http://191.101.113.33:8000',
            'Referer' => 'http://191.101.113.33:8000/login',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['user', 'token', 'token_type']);
    }
}
