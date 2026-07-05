<?php

namespace App\Http\Controllers\Api\Notification;

use App\Http\Controllers\Controller;
use App\Services\Notification\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $notifications = $this->notificationService->list($request->user());

        return response()->json([
            'data' => $notifications->map(fn ($n) => $this->notificationService->format($n)),
            'meta' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'total' => $notifications->total(),
            ],
            'unread_count' => $this->notificationService->unreadCount($request->user()),
        ]);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        return response()->json([
            'count' => $this->notificationService->unreadCount($request->user()),
        ]);
    }

    public function markAsRead(Request $request, string $id): JsonResponse
    {
        $this->notificationService->markAsRead($request->user(), $id);

        return response()->json(['message' => 'اعلان خوانده شد.']);
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        $this->notificationService->markAllAsRead($request->user());

        return response()->json(['message' => 'همه اعلان‌ها خوانده شدند.']);
    }
}
