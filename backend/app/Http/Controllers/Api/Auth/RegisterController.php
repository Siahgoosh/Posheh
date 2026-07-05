<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Services\Auth\RegisterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RegisterController extends Controller
{
    public function __construct(
        private readonly RegisterService $registerService,
    ) {}

    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'mobile' => ['required', 'string', 'regex:/^09\d{9}$/'],
            'code' => ['required', 'string', 'size:6'],
            'name' => ['required', 'string', 'max:255'],
            'office_name' => ['required', 'string', 'max:255'],
        ]);

        $result = $this->registerService->registerManager(
            $request->input('mobile'),
            $request->input('code'),
            $request->input('name'),
            $request->input('office_name'),
        );

        return response()->json([
            'user' => new UserResource($result['user']),
            'token' => $result['token'],
            'token_type' => $result['token_type'],
            'message' => $result['message'],
            'requires_subscription' => $result['requires_subscription'],
        ], 201);
    }
}
