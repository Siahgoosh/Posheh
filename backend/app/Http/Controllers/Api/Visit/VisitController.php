<?php

namespace App\Http\Controllers\Api\Visit;

use App\Http\Controllers\Controller;
use App\Services\Visit\VisitService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VisitController extends Controller
{
    public function __construct(private readonly VisitService $visitService) {}

    public function index(Request $request): JsonResponse
    {
        $year = (int) $request->query('year', \Morilog\Jalali\Jalalian::now()->getYear());
        $month = (int) $request->query('month', \Morilog\Jalali\Jalalian::now()->getMonth());

        return response()->json([
            'data' => $this->visitService->listForMonth($request->user(), $year, $month),
        ]);
    }

    public function upcoming(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->visitService->upcoming($request->user()),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'property_id' => ['required', 'integer', 'exists:properties,id'],
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'visit_at' => ['required', 'date'],
            'duration_minutes' => ['nullable', 'integer', 'min:15', 'max:480'],
            'status' => ['nullable', 'string', 'in:scheduled,completed,cancelled'],
            'notes' => ['nullable', 'string'],
        ]);

        $visit = $this->visitService->create($request->user(), $data);

        return response()->json(['data' => $visit, 'message' => 'بازدید ثبت شد.'], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'property_id' => ['sometimes', 'integer', 'exists:properties,id'],
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'visit_at' => ['sometimes', 'date'],
            'duration_minutes' => ['nullable', 'integer', 'min:15', 'max:480'],
            'status' => ['nullable', 'string', 'in:scheduled,completed,cancelled'],
            'notes' => ['nullable', 'string'],
        ]);

        $visit = $this->visitService->update($request->user(), $id, $data);

        return response()->json(['data' => $visit, 'message' => 'بازدید ویرایش شد.']);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $this->visitService->delete($request->user(), $id);

        return response()->json(['message' => 'بازدید حذف شد.']);
    }
}
