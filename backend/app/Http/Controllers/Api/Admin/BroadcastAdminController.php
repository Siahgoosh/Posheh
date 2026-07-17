<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\BroadcastMessage;
use App\Services\Notifications\BroadcastNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BroadcastAdminController extends Controller
{
    public function __construct(
        private readonly BroadcastNotificationService $broadcasts,
    ) {}

    public function index(): JsonResponse
    {
        $items = BroadcastMessage::with('creator:id,name')
            ->latest()
            ->paginate(30);

        return response()->json($items);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'body' => ['required', 'string', 'max:2000'],
            'link_url' => ['nullable', 'url', 'max:500'],
            'image_url' => ['nullable', 'string', 'max:500'],
            'action_label' => ['nullable', 'string', 'max:40'],
            'priority' => ['nullable', 'in:low,normal,high,urgent'],
            'target_platforms' => ['nullable', 'array'],
            'target_platforms.*' => ['string', 'in:all,web,android,windows,ios'],
            'target_roles' => ['nullable', 'array'],
            'target_roles.*' => ['string'],
            'style' => ['nullable', 'array'],
        ]);

        $message = $this->broadcasts->send($request->user(), $data);

        return response()->json([
            'message' => 'اعلان برای کاربران ارسال شد.',
            'data' => $message,
        ], 201);
    }
}
