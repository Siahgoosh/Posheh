<?php

namespace App\Http\Controllers\Api\Office;

use App\Http\Controllers\Controller;
use App\Http\Resources\PropertyResource;
use App\Http\Resources\UserResource;
use App\Services\Office\OfficeService;
use App\Services\Office\OfficeSiteService;
use App\Services\Property\PropertyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OfficeController extends Controller
{
    public function __construct(
        private readonly OfficeService $officeService,
        private readonly OfficeSiteService $siteService,
        private readonly PropertyService $propertyService,
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

    public function requestWebsite(Request $request): JsonResponse
    {
        $data = $request->validate([
            'subdomain' => ['required', 'string', 'min:3', 'max:63', 'regex:/^[a-z0-9-]+$/'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        $office = $this->siteService->requestWebsite($request->user(), $data);

        return response()->json([
            'data' => $office,
            'message' => 'درخواست وبسایت ثبت شد. پس از تأیید مدیر کل در آدرس '.$data['subdomain'].'.posheapp.ir منتشر می‌شود.',
        ]);
    }

    public function websiteStatus(Request $request): JsonResponse
    {
        $office = $request->user()->office;

        return response()->json([
            'data' => [
                'subdomain' => $office->subdomain,
                'website_status' => $office->website_status,
                'website_description' => $office->website_description,
                'website_published_at' => $office->website_published_at?->toIso8601String(),
                'url' => $office->subdomain ? 'https://'.$office->subdomain.'.posheapp.ir' : null,
            ],
        ]);
    }

    public function visitRequests(Request $request): JsonResponse
    {
        $office = $request->user()->office;

        $requests = \App\Models\OfficeVisitRequest::with('property:id,code')
            ->where('office_id', $office->id)
            ->latest()
            ->limit(100)
            ->get()
            ->map(fn ($r) => [
                'id' => $r->id,
                'name' => $r->name,
                'mobile' => $r->mobile,
                'email' => $r->email,
                'property_code' => $r->property?->code,
                'preferred_date' => $r->preferred_date,
                'preferred_time' => $r->preferred_time,
                'message' => $r->message,
                'status' => $r->status,
                'created_at' => $r->created_at?->toIso8601String(),
            ]);

        return response()->json(['data' => $requests]);
    }

    public function pendingWebsiteProperties(Request $request): JsonResponse
    {
        abort_unless($request->user()->canManageOffice(), 403);

        $properties = $this->propertyService->pendingWebsiteProperties($request->user());

        return response()->json([
            'data' => PropertyResource::collection($properties),
        ]);
    }

    public function createSitePost(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'excerpt' => ['nullable', 'string'],
            'body' => ['nullable', 'string'],
            'property_id' => ['nullable', 'integer'],
        ]);

        $post = $this->siteService->publishPost($request->user(), $data);

        return response()->json(['data' => $post, 'message' => 'پست وبسایت منتشر شد.'], 201);
    }
}
