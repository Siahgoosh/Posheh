<?php

namespace App\Http\Controllers\Api\Office;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Services\Office\OfficeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OfficeController extends Controller
{
    public function __construct(
        private readonly OfficeService $officeService,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string'],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string'],
        ]);

        $office = $this->officeService->createOffice($request->all(), $request->user());

        return response()->json([
            'data' => $office,
            'message' => 'دفتر با موفقیت ایجاد شد.',
        ], 201);
    }

    public function team(Request $request): JsonResponse
    {
        $members = $this->officeService->getTeamMembers($request->user()->office_id);

        return response()->json([
            'data' => UserResource::collection($members),
        ]);
    }

    public function invite(Request $request): JsonResponse
    {
        $request->validate([
            'mobile' => ['required', 'string', 'regex:/^09\d{9}$/'],
            'role' => ['nullable', 'string', 'in:consultant,office_manager'],
        ]);

        $invitation = $this->officeService->inviteConsultant(
            $request->user(),
            $request->input('mobile'),
            $request->input('role', 'consultant')
        );

        return response()->json([
            'data' => $invitation,
            'message' => 'دعوتنامه با موفقیت ارسال شد.',
        ], 201);
    }

    public function updateSettings(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user->canManageOffice(), 403);

        $data = $request->validate([
            'telegram_bot_token' => ['nullable', 'string', 'max:255'],
            'whatsapp_phone' => ['nullable', 'string', 'max:20'],
            'whatsapp_auto_reply' => ['nullable', 'string', 'max:500'],
            'brand_color' => ['nullable', 'string', 'max:20'],
            'brand_name' => ['nullable', 'string', 'max:100'],
            'show_on_website' => ['sometimes', 'boolean'],
        ]);

        $office = $user->office;
        $settings = $office->settings ?? [];

        if (isset($data['brand_color'])) {
            $settings['brand_color'] = $data['brand_color'];
        }
        if (isset($data['brand_name'])) {
            $settings['brand_name'] = $data['brand_name'];
        }

        $office->update([
            'telegram_bot_token' => $data['telegram_bot_token'] ?? $office->telegram_bot_token,
            'whatsapp_config' => array_merge($office->whatsapp_config ?? [], array_filter([
                'phone' => $data['whatsapp_phone'] ?? null,
                'auto_reply' => $data['whatsapp_auto_reply'] ?? null,
            ])),
            'settings' => $settings,
            'show_on_website' => $data['show_on_website'] ?? $office->show_on_website,
        ]);

        return response()->json(['data' => $office->fresh(), 'message' => 'تنظیمات ذخیره شد.']);
    }
}
