<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\PasswordResetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PasswordResetController extends Controller
{
    public function __construct(private readonly PasswordResetService $resetService) {}

    public function forgot(Request $request): JsonResponse
    {
        $data = $request->validate([
            'channel' => ['required', 'in:email,sms'],
            'login' => ['required', 'string', 'max:255'],
        ]);

        $result = $this->resetService->forgot($data['channel'], $data['login']);

        return response()->json($result);
    }

    public function reset(Request $request): JsonResponse
    {
        $this->resetService->reset($request->all());

        return response()->json(['message' => 'رمز عبور با موفقیت تنظیم شد. اکنون می‌توانید وارد شوید.']);
    }
}
