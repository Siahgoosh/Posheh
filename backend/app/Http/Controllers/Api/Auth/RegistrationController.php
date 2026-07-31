<?php

namespace App\Http\Controllers\Api\Auth;

use App\DTOs\Auth\VerifyOtpDTO;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Services\Auth\RegistrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegistrationController extends Controller
{
    public function __construct(
        private readonly RegistrationService $registrationService,
    ) {}

    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'registration_token' => ['nullable', 'string'],
            'plan_slug' => ['required', 'string', Rule::exists('subscription_plans', 'slug')->where('is_active', true)],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'username' => [
                'required',
                'string',
                'min:3',
                'max:50',
                'regex:/^[a-zA-Z0-9._-]+$/',
                Rule::unique('users', 'username'),
            ],
            'password' => ['required', 'string', 'confirmed', Password::min(8)],
            'mobile' => ['required', 'string', 'regex:/^09\d{9}$/', Rule::unique('users', 'mobile')],
            'first_name' => ['required_if:plan_slug,solo', 'nullable', 'string', 'max:100'],
            'last_name' => ['required_if:plan_slug,solo', 'nullable', 'string', 'max:100'],
            'manager_name' => ['nullable', 'string', 'max:100'],
            'office_name' => ['required', 'string', 'max:255'],
            'office_phone' => ['nullable', 'string', 'max:20'],
            'office_address' => ['required', 'string', 'max:500'],
            'office_city' => ['nullable', 'string', 'max:100'],
            'office_description' => ['nullable', 'string', 'max:1000'],
            'telegram_bot_token' => ['nullable', 'string', 'max:255'],
            'whatsapp_phone' => ['nullable', 'string', 'max:20'],
            'logo' => ['nullable', 'image', 'max:5120'],
            'device_id' => ['nullable', 'string'],
            'device_name' => ['nullable', 'string'],
            'platform' => ['nullable', 'string'],
        ], [
            'first_name.required_if' => 'نام الزامی است.',
            'last_name.required_if' => 'نام خانوادگی الزامی است.',
            'office_name.required' => 'نام دفتر املاک الزامی است.',
            'office_address.required' => 'آدرس دفتر الزامی است.',
            'mobile.regex' => 'شماره موبایل باید ۱۱ رقم و با ۰۹ شروع شود.',
            'username.regex' => 'نام کاربری فقط حروف انگلیسی، عدد، نقطه، خط تیره و زیرخط مجاز است.',
            'plan_slug.exists' => 'پلن انتخاب‌شده معتبر نیست.',
        ]);

        $device = new VerifyOtpDTO(
            mobile: $data['mobile'],
            code: '',
            deviceId: $data['device_id'] ?? null,
            deviceName: $data['device_name'] ?? null,
            platform: $data['platform'] ?? null,
        );

        $payload = array_merge($data, ['logo' => $request->file('logo')]);

        $result = ! empty($data['registration_token'])
            ? $this->registrationService->register($data['registration_token'], $payload, $device)
            : $this->registrationService->registerWithPassword($payload, $device);

        return response()->json([
            'user' => new UserResource($result['user']),
            'token' => $result['token'],
            'token_type' => $result['token_type'],
            'expires_at' => $result['expires_at'],
            'access' => $result['access'],
        ], 201);
    }
}
