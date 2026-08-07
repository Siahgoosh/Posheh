<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Notification\UserNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct(private readonly UserNotificationService $notifications) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->notifications->inbox($request->user())]);
    }

    public function markRead(Request $request, string $id): JsonResponse
    {
        $this->notifications->markRead($request->user(), $id);

        return response()->json(['message' => 'خوانده شد.']);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $this->notifications->markAllRead($request->user());

        return response()->json(['message' => 'همه اعلان‌ها خوانده شد.']);
    }
}
