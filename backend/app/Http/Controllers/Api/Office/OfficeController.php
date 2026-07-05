<?php

namespace App\Http\Controllers\Api\Office;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Services\Office\OfficeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OfficeController extends Controller
{
    public function __construct(
        private readonly OfficeService $officeService,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string'],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string'],
        ]);

        $office = $this->officeService->createOffice($request->all(), $request->user());

        return response()->json([
            'data' => $office,
            'message' => 'دفتر با موفقیت ایجاد شد.',
        ], 201);
    }

    public function team(Request $request): JsonResponse
    {
        $members = $this->officeService->getTeamMembers($request->user()->office_id);

        return response()->json([
            'data' => UserResource::collection($members),
        ]);
    }

    public function invite(Request $request): JsonResponse
    {
        $request->validate([
            'mobile' => ['required', 'string', 'regex:/^09\d{9}$/'],
            'role' => ['nullable', 'string', 'in:consultant,office_manager'],
        ]);

        $invitation = $this->officeService->inviteConsultant(
            $request->user(),
            $request->input('mobile'),
            $request->input('role', 'consultant')
        );

        return response()->json([
            'data' => $invitation,
            'message' => 'دعوتنامه با موفقیت ارسال شد.',
        ], 201);
    }
}
