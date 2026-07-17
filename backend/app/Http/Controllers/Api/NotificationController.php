<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Notifications\BroadcastNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct(
        private readonly BroadcastNotificationService $broadcasts,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $platform = $request->query('platform', 'web');

        return response()->json([
            'data' => $this->broadcasts->forUser($request->user(), (string) $platform),
        ]);
    }

    public function markRead(Request $request, int $id): JsonResponse
    {
        $this->broadcasts->markRead($request->user(), $id);

        return response()->json(['message' => 'خوانده شد.']);
    }
}
