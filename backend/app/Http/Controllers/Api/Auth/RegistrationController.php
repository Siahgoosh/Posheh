<?php

namespace App\Http\Controllers\Api\Auth;

use App\DTOs\Auth\VerifyOtpDTO;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Services\Auth\RegistrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RegistrationController extends Controller
{
    public function __construct(
        private readonly RegistrationService $registrationService,
    ) {}

    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'registration_token' => ['required', 'string'],
            'plan_slug' => ['required', 'string', Rule::exists('subscription_plans', 'slug')->where('is_active', true)],
            'first_name' => ['required_if:plan_slug,solo', 'nullable', 'string', 'max:100'],
            'last_name' => ['required_if:plan_slug,solo', 'nullable', 'string', 'max:100'],
            'manager_name' => ['nullable', 'string', 'max:100'],
            'office_name' => ['required_unless:plan_slug,solo', 'nullable', 'string', 'max:255'],
            'office_phone' => ['nullable', 'string', 'max:20'],
            'office_address' => ['nullable', 'string', 'max:500'],
            'office_city' => ['nullable', 'string', 'max:100'],
            'office_description' => ['nullable', 'string', 'max:1000'],
            'telegram_bot_token' => ['nullable', 'string', 'max:255'],
            'whatsapp_phone' => ['nullable', 'string', 'max:20'],
            'logo' => ['nullable', 'image', 'max:5120'],
            'device_id' => ['nullable', 'string'],
            'device_name' => ['nullable', 'string'],
            'platform' => ['nullable', 'string'],
        ]);

        $result = $this->registrationService->register(
            $data['registration_token'],
            array_merge($data, ['logo' => $request->file('logo')]),
            new VerifyOtpDTO(
                mobile: '',
                code: '',
                deviceId: $data['device_id'] ?? null,
                deviceName: $data['device_name'] ?? null,
                platform: $data['platform'] ?? null,
            )
        );

        return response()->json([
            'user' => new UserResource($result['user']),
            'token' => $result['token'],
            'token_type' => $result['token_type'],
            'expires_at' => $result['expires_at'],
            'access' => $result['access'],
        ], 201);
    }
}
