<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Commission\CommissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommissionController extends Controller
{
    public function __construct(private readonly CommissionService $commissions) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->commissions->list($request->user(), $request->query('status')),
            'summary' => $this->commissions->summary($request->user()),
        ]);
    }

    public function settings(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->commissions->getSettings($request->user())]);
    }

    public function updateSettings(Request $request): JsonResponse
    {
        $data = $request->validate([
            'sale_rate_percent' => ['required', 'integer', 'min:1', 'max:100'],
            'rent_rate_percent' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        return response()->json([
            'data' => $this->commissions->updateSettings($request->user(), $data),
            'message' => 'تنظیمات کمیسیون ذخیره شد.',
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'title' => ['required', 'string', 'max:255'],
            'base_amount' => ['required', 'integer', 'min:0'],
            'rate_percent' => ['required', 'integer', 'min:1', 'max:100'],
            'property_id' => ['nullable', 'integer'],
            'notes' => ['nullable', 'string'],
        ]);

        $commission = $this->commissions->createManual($request->user(), $data);

        return response()->json(['data' => $commission, 'message' => 'کمیسیون ثبت شد.'], 201);
    }

    public function markPaid(Request $request, int $id): JsonResponse
    {
        $commission = $this->commissions->markPaid($request->user(), $id);

        return response()->json(['data' => $commission, 'message' => 'کمیسیون تسویه شد.']);
    }
}
