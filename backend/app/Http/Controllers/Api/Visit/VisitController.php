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

    public function inbound(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->visitService->inboundRequests($request->user()),
        ]);
    }

    public function convertInbound(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'property_id' => ['nullable', 'integer', 'exists:properties,id'],
            'visit_at' => ['nullable', 'date'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $visit = $this->visitService->convertInboundRequest($request->user(), $id, $data);

        return response()->json([
            'data' => $visit,
            'message' => 'درخواست به بازدید زمان‌بندی‌شده تبدیل شد.',
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'property_id' => ['required', 'integer', 'exists:properties,id'],
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'customer_mobile' => ['nullable', 'string', 'max:20'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'visit_at' => ['required', 'date'],
            'duration_minutes' => ['nullable', 'integer', 'min:15', 'max:480'],
            'status' => ['nullable', 'string', 'in:scheduled,completed,cancelled'],
            'notes' => ['nullable', 'string'],
        ]);

        // Allow creating customer inline (consultant / manager)
        if (empty($data['customer_id']) && ! empty($data['customer_mobile'])) {
            $customer = \App\Models\Customer::firstOrCreate(
                [
                    'office_id' => $request->user()->office_id,
                    'mobile' => $data['customer_mobile'],
                ],
                [
                    'name' => $data['customer_name'] ?? 'مشتری بازدید',
                    'created_by' => $request->user()->id,
                    'source' => 'visit',
                ]
            );
            $data['customer_id'] = $customer->id;
        }
        unset($data['customer_name'], $data['customer_mobile']);

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
