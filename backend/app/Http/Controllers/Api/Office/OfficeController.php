<?php

namespace App\Http\Controllers\Api\Office;

use App\Http\Controllers\Controller;
use App\Http\Resources\PropertyResource;
use App\Http\Resources\UserResource;
use App\Services\Office\OfficeService;
use App\Services\Office\OfficeSiteService;
use App\Services\Office\DomainService;
use App\Services\Office\TelegramBotService;
use App\Services\Property\PropertyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OfficeController extends Controller
{
    public function __construct(
        private readonly OfficeService $officeService,
        private readonly OfficeSiteService $siteService,
        private readonly DomainService $domainService,
        private readonly TelegramBotService $telegramBot,
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
        $data = $request->validate([
            'mobile' => ['required', 'string', 'regex:/^09\d{9}$/'],
            'name' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'username' => ['nullable', 'string', 'min:3', 'max:50', 'regex:/^[a-zA-Z0-9_]+$/', 'unique:users,username'],
            'password' => ['nullable', 'string', 'min:8'],
            'role' => ['nullable', 'string', 'in:consultant,office_manager'],
        ]);

        $invitation = $this->officeService->inviteConsultant($request->user(), $data);

        return response()->json([
            'data' => $invitation,
            'message' => 'عضو جدید با موفقیت اضافه شد.',
        ], 201);
    }

    public function settings(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user->canManageOffice(), 403);
        $office = $user->office;
        $wa = $office->whatsapp_config ?? [];

        return response()->json([
            'data' => [
                'telegram_bot_token' => $office->telegram_bot_token,
                'telegram_admin_chat_id' => $office->telegram_admin_chat_id,
                'whatsapp_phone' => $wa['phone'] ?? '',
                'whatsapp_auto_reply' => $wa['auto_reply'] ?? '',
                'brand_color' => ($office->settings ?? [])['brand_color'] ?? '#6366f1',
                'brand_name' => ($office->settings ?? [])['brand_name'] ?? '',
                'show_on_website' => $office->show_on_website,
                'office_slug' => $office->slug,
                'webhook_url' => rtrim(config('app.url'), '/').'/api/v1/bots/telegram/'.$office->slug,
            ],
        ]);
    }

    public function updateSettings(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user->canManageOffice(), 403);

        $data = $request->validate([
            'telegram_bot_token' => ['nullable', 'string', 'max:255'],
            'telegram_admin_chat_id' => ['nullable', 'string', 'max:50'],
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
            'telegram_bot_token' => array_key_exists('telegram_bot_token', $data)
                ? $data['telegram_bot_token']
                : $office->telegram_bot_token,
            'telegram_admin_chat_id' => array_key_exists('telegram_admin_chat_id', $data)
                ? $data['telegram_admin_chat_id']
                : $office->telegram_admin_chat_id,
            'whatsapp_config' => array_merge($office->whatsapp_config ?? [], array_filter([
                'phone' => $data['whatsapp_phone'] ?? null,
                'auto_reply' => $data['whatsapp_auto_reply'] ?? null,
            ])),
            'settings' => $settings,
            'show_on_website' => $data['show_on_website'] ?? $office->show_on_website,
        ]);

        $office = $office->fresh();
        $telegramResult = null;

        if (trim((string) $office->telegram_bot_token) !== '') {
            $telegramResult = $this->telegramBot->configureWebhook($office);
        }

        return response()->json([
            'data' => $office,
            'message' => $telegramResult
                ? ($telegramResult['message'] ?? 'تنظیمات ذخیره شد.')
                : 'تنظیمات ذخیره شد.',
            'telegram' => $telegramResult,
        ]);
    }

    public function reconnectTelegramWebhook(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user->canManageOffice(), 403);

        $office = $user->office;
        if (! trim((string) $office->telegram_bot_token)) {
            return response()->json([
                'message' => 'ابتدا توکن ربات را ذخیره کنید.',
                'telegram' => ['ok' => false, 'message' => 'توکن ربات خالی است.'],
            ], 422);
        }

        $result = $this->telegramBot->configureWebhook($office);

        return response()->json([
            'message' => $result['message'],
            'telegram' => $result,
        ], $result['ok'] ? 200 : 422);
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
            'data' => array_merge([
                'subdomain' => $office->subdomain,
                'website_status' => $office->website_status,
                'website_description' => $office->website_description,
                'website_published_at' => $office->website_published_at?->toIso8601String(),
                'url' => $office->subdomain ? 'https://'.$office->subdomain.'.posheapp.ir' : null,
            ], $this->domainService->status($office)),
        ]);
    }

    public function domainStatus(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->domainService->status($request->user()->office)]);
    }

    public function checkDomain(Request $request): JsonResponse
    {
        $data = $request->validate([
            'domain_name' => ['required', 'string', 'max:255'],
        ]);

        $result = $this->domainService->checkAvailability($data['domain_name']);

        return response()->json(['data' => $result]);
    }

    public function payDomain(Request $request): JsonResponse
    {
        $data = $request->validate([
            'domain_name' => ['required', 'string', 'max:255'],
        ]);

        $result = $this->domainService->initiatePayment($request->user(), $data['domain_name']);

        return response()->json(['data' => $result]);
    }

    public function connectDomain(Request $request): JsonResponse
    {
        $data = $request->validate([
            'domain_name' => ['required', 'string', 'max:255'],
        ]);

        $office = $this->domainService->connectDomain($request->user(), $data['domain_name']);

        return response()->json([
            'data' => $this->domainService->status($office),
            'message' => 'دامنه ثبت شد. رکوردهای DNS را تنظیم کنید.',
        ]);
    }

    public function verifyDomain(Request $request): JsonResponse
    {
        $office = $request->user()->office;
        $verified = $this->domainService->verifyDns($office);

        return response()->json([
            'verified' => $verified,
            'data' => $this->domainService->status($office->fresh()),
            'message' => $verified ? 'دامنه تأیید شد.' : 'تأیید DNS ناموفق بود.',
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
