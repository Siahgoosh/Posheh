<?php

namespace App\Http\Controllers\Api\Communication;

use App\Http\Controllers\Controller;
use App\Modules\Communication\Application\DTOs\CaptureLeadDTO;
use App\Modules\Communication\Application\DTOs\VisitorInitDTO;
use App\Modules\Communication\Application\Services\ConversationService;
use App\Modules\Communication\Application\Services\LeadCaptureService;
use App\Modules\Communication\Application\Services\MessageService;
use App\Modules\Communication\Application\Services\VisitorTrackingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class CommunicationPublicController extends Controller
{
    public function __construct(
        private readonly VisitorTrackingService $visitors,
        private readonly LeadCaptureService $leads,
        private readonly ConversationService $conversations,
        private readonly MessageService $messages,
    ) {}

    public function config(): JsonResponse
    {
        return response()->json([
            'data' => [
                'provinces' => config('communication.iran_provinces'),
                'activity_types' => config('communication.activity_types'),
                'request_types' => config('communication.request_types'),
                'heartbeat_interval' => config('communication.heartbeat_interval_seconds', 25),
            ],
        ]);
    }

    public function health(): JsonResponse
    {
        $installed = Schema::hasTable('comm_visitors')
            && Schema::hasTable('comm_conversations')
            && Schema::hasTable('comm_messages');

        return response()->json([
            'ok' => $installed,
            'installed' => $installed,
            'message' => $installed
                ? 'Communication module is ready.'
                : 'Run: php artisan communication:install',
        ], $installed ? 200 : 503);
    }

    public function init(Request $request): JsonResponse
    {
        if (! Schema::hasTable('comm_visitors')) {
            return response()->json([
                'message' => 'ماژول مرکز ارتباطات نصب نشده. روی سرور: php artisan communication:install',
                'code' => 'comm_not_installed',
            ], 503);
        }

        $data = $request->validate([
            'visitor_token' => ['nullable', 'uuid'],
            'session_key' => ['required', 'string', 'max:64'],
            'current_page' => ['nullable', 'string', 'max:500'],
            'referrer' => ['nullable', 'string', 'max:500'],
            'landing_page' => ['nullable', 'string', 'max:500'],
            'language' => ['nullable', 'string', 'max:20'],
            'timezone' => ['nullable', 'string', 'max:60'],
            'screen_resolution' => ['nullable', 'string', 'max:20'],
            'utm_source' => ['nullable', 'string', 'max:120'],
            'utm_campaign' => ['nullable', 'string', 'max:120'],
            'utm_medium' => ['nullable', 'string', 'max:120'],
            'utm_term' => ['nullable', 'string', 'max:120'],
            'utm_content' => ['nullable', 'string', 'max:120'],
        ]);

        $dto = VisitorInitDTO::fromRequest(
            $data,
            (string) $request->ip(),
            $request->userAgent(),
            $request->user()?->id,
        );

        $result = $this->visitors->init($dto);

        return response()->json(['data' => $result]);
    }

    public function heartbeat(Request $request): JsonResponse
    {
        $data = $request->validate([
            'visitor_token' => ['required', 'uuid'],
            'session_key' => ['required', 'string', 'max:64'],
            'current_page' => ['nullable', 'string', 'max:500'],
            'time_on_site_seconds' => ['nullable', 'integer', 'min:0'],
            'scroll_depth' => ['nullable', 'integer', 'min:0', 'max:100'],
            'click_count_delta' => ['nullable', 'integer', 'min:0'],
            'mouse_movement_delta' => ['nullable', 'integer', 'min:0'],
        ]);

        $this->visitors->heartbeat(
            $data['visitor_token'],
            $data['session_key'],
            $data,
        );

        return response()->json(['ok' => true]);
    }

    public function event(Request $request): JsonResponse
    {
        $data = $request->validate([
            'visitor_token' => ['required', 'uuid'],
            'session_key' => ['required', 'string', 'max:64'],
            'event_type' => ['required', 'string', 'max:40'],
            'path' => ['nullable', 'string', 'max:500'],
            'meta' => ['nullable', 'array'],
        ]);

        $this->visitors->trackEvent(
            $data['visitor_token'],
            $data['session_key'],
            $data['event_type'],
            $data['path'] ?? null,
            $data['meta'] ?? [],
        );

        return response()->json(['ok' => true]);
    }

    public function captureLead(Request $request): JsonResponse
    {
        if (! Schema::hasTable('comm_leads')) {
            return response()->json([
                'message' => 'ماژول مرکز ارتباطات نصب نشده. php artisan communication:install',
                'code' => 'comm_not_installed',
            ], 503);
        }

        $data = $request->validate([
            'visitor_token' => ['required', 'uuid'],
            'session_key' => ['required', 'string', 'max:64'],
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['nullable', 'string', 'max:80'],
            'mobile' => ['required', 'string', 'max:20'],
            'mobile_verified' => ['nullable', 'boolean'],
            'email' => ['nullable', 'email', 'max:120'],
            'province' => ['nullable', 'string', 'max:80'],
            'city' => ['nullable', 'string', 'max:80'],
            'office_name' => ['nullable', 'string', 'max:120'],
            'role_title' => ['nullable', 'string', 'max:80'],
            'staff_count' => ['nullable', 'integer', 'min:0'],
            'activity_type' => ['nullable', 'string', 'max:80'],
            'request_type' => ['nullable', 'string', 'max:80'],
            'budget' => ['nullable', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:2000'],
            'source_channel' => ['nullable', 'string', 'max:40'],
            'tracking_snapshot' => ['nullable', 'array'],
        ]);

        $dto = CaptureLeadDTO::fromRequest($data, (string) $request->ip(), $request->userAgent());
        $result = $this->leads->capture($dto);

        return response()->json([
            'data' => [
                'lead_id' => $result['lead']->id,
                'conversation_uuid' => $result['conversation_uuid'],
                'lead_score' => $result['lead']->lead_score,
            ],
            'message' => 'اطلاعات شما ثبت شد. به زودی پاسخ می‌دهیم.',
        ], 201);
    }

    public function messages(Request $request, string $conversationUuid): JsonResponse
    {
        $data = $request->validate([
            'visitor_token' => ['required', 'uuid'],
        ]);

        $conversation = $this->conversations->findByUuidForVisitor($conversationUuid, $data['visitor_token']);
        if (! $conversation) {
            return response()->json(['message' => 'گفتگو یافت نشد.'], 404);
        }

        $this->messages->markReadByVisitor($conversation);

        return response()->json([
            'data' => $conversation->messages()
                ->where('is_internal', false)
                ->orderBy('created_at')
                ->get(['id', 'sender_type', 'body', 'created_at', 'read_by_visitor_at']),
        ]);
    }

    public function sendMessage(Request $request, string $conversationUuid): JsonResponse
    {
        $data = $request->validate([
            'visitor_token' => ['required', 'uuid'],
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $conversation = $this->conversations->findByUuidForVisitor($conversationUuid, $data['visitor_token']);
        if (! $conversation) {
            return response()->json(['message' => 'گفتگو یافت نشد.'], 404);
        }

        $message = $this->messages->sendFromVisitor($conversation, $data['body']);

        return response()->json([
            'data' => [
                'id' => $message->id,
                'body' => $message->body,
                'created_at' => $message->created_at?->toIso8601String(),
            ],
        ], 201);
    }
}
