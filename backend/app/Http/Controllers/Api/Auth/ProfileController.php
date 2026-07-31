<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Services\Auth\ProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function __construct(private readonly ProfileService $profile) {}

    public function update(Request $request): JsonResponse
    {
        $user = $this->profile->update($request->user(), $request->all());

        return response()->json([
            'user' => new UserResource($user),
            'message' => 'پروفایل به‌روزرسانی شد.',
        ]);
    }

    public function changePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'confirmed', 'min:8'],
        ]);

        $this->profile->changePassword(
            $request->user(),
            $request->input('current_password'),
            $request->input('password'),
        );

        return response()->json(['message' => 'رمز عبور با موفقیت تغییر کرد.']);
    }
}
