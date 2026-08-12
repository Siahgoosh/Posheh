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
            'crm_deal_id' => ['nullable', 'integer'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'visit_at' => ['sometimes', 'date'],
            'duration_minutes' => ['nullable', 'integer', 'min:15', 'max:480'],
            'status' => ['nullable', 'string', 'in:scheduled,completed,cancelled,confirmed,no_show,rescheduled'],
            'notes' => ['nullable', 'string'],
            'customer_reaction' => ['nullable', 'string', 'max:40'],
            'property_rating' => ['nullable', 'integer', 'min:1', 'max:5'],
            'price_opinion' => ['nullable', 'string', 'max:40'],
            'likelihood_to_buy' => ['nullable', 'string', 'max:40'],
            'next_action' => ['nullable', 'string', 'max:255'],
        ]);

        $visit = $this->visitService->update($request->user(), $id, $data);

        return response()->json(['data' => $visit, 'message' => 'بازدید ویرایش شد.']);
    }

    public function complete(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'customer_reaction' => ['nullable', 'string', 'max:40'],
            'property_rating' => ['nullable', 'integer', 'min:1', 'max:5'],
            'price_opinion' => ['nullable', 'string', 'max:40'],
            'likelihood_to_buy' => ['nullable', 'string', 'in:very_interested,interested,maybe,not_interested'],
            'next_action' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $visit = $this->visitService->complete($request->user(), $id, $data);

        return response()->json(['data' => $visit, 'message' => 'بازدید تکمیل شد.']);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $this->visitService->delete($request->user(), $id);

        return response()->json(['message' => 'بازدید حذف شد.']);
    }
}
