<?php

namespace App\Http\Controllers\Api\Accounting;

use App\Http\Controllers\Controller;
use App\Services\Accounting\CommissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommissionController extends Controller
{
    public function __construct(private readonly CommissionService $service) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->service->list($request->user())]);
    }

    public function summary(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->service->summary($request->user())]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'property_id' => ['nullable', 'integer'],
            'deal_id' => ['nullable', 'integer'],
            'total_amount' => ['required', 'integer', 'min:0'],
            'status' => ['nullable', 'in:pending,paid'],
            'splits' => ['nullable', 'array'],
            'splits.*.user_id' => ['required_with:splits', 'integer'],
            'splits.*.percentage' => ['required_with:splits', 'numeric'],
            'splits.*.role' => ['nullable', 'string'],
        ]);

        $commission = $this->service->createWithSplits($request->user(), $data);

        return response()->json(['data' => $commission, 'message' => 'کمیسیون ثبت شد.'], 201);
    }
}
