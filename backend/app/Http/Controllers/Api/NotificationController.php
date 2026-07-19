<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Notifications\BroadcastNotificationService;
use App\Services\Settings\SystemSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct(
        private readonly BroadcastNotificationService $broadcasts,
        private readonly SystemSettingsService $settings,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $platform = $request->query('platform', 'web');
        $items = $this->broadcasts->forUser($request->user(), (string) $platform);

        return response()->json([
            'data' => $items,
            'unread_count' => $items->where('is_read', false)->count(),
            'poll_interval' => max(15, (int) ($this->settings->get('notification_poll_interval_seconds', '30') ?: 30)),
        ]);
    }

    public function markRead(Request $request, int $id): JsonResponse
    {
        $this->broadcasts->markRead($request->user(), $id);

        return response()->json(['message' => 'خوانده شد.']);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $count = $this->broadcasts->markAllRead($request->user());

        return response()->json(['message' => "همه اعلان‌ها خوانده شد ({$count})."]);
    }

    public function dismiss(Request $request, int $id): JsonResponse
    {
        $this->broadcasts->dismiss($request->user(), $id);

        return response()->json(['message' => 'اعلان حذف شد.']);
    }
}
