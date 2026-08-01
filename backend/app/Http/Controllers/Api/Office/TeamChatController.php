<?php

namespace App\Http\Controllers\Api\Office;

use App\Http\Controllers\Controller;
use App\Services\Office\TeamChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TeamChatController extends Controller
{
    public function __construct(private readonly TeamChatService $chat) {}

    public function index(Request $request): JsonResponse
    {
        $afterId = $request->integer('after_id') ?: null;

        return response()->json([
            'data' => $this->chat->list($request->user(), $afterId),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:4000'],
        ]);

        $message = $this->chat->send($request->user(), $data['message']);

        return response()->json(['data' => $message], 201);
    }
}
