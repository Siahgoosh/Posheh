<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Http\Resources\PropertyResource;
use App\Services\Customer\CustomerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function __construct(private readonly CustomerService $customerService) {}

    public function index(Request $request): JsonResponse
    {
        $customers = $this->customerService->list(
            $request->user(),
            $request->query('q'),
            $request->query('priority'),
        );

        return response()->json([
            'data' => $customers->items(),
            'meta' => [
                'current_page' => $customers->currentPage(),
                'last_page' => $customers->lastPage(),
                'total' => $customers->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'mobile' => ['nullable', 'string', 'max:20'],
            'national_id' => ['nullable', 'string', 'max:20'],
            'priority' => ['nullable', 'string', 'in:normal,vip'],
            'budget_min' => ['nullable', 'integer', 'min:0'],
            'budget_max' => ['nullable', 'integer', 'min:0'],
            'preferred_type' => ['nullable', 'string', 'max:30'],
            'preferred_city' => ['nullable', 'string', 'max:100'],
            'preferred_district' => ['nullable', 'string', 'max:100'],
            'min_area' => ['nullable', 'integer', 'min:0'],
            'max_area' => ['nullable', 'integer', 'min:0'],
            'min_rooms' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $customer = $this->customerService->create($request->user(), $data);

        return response()->json(['data' => $customer, 'message' => 'مشتری ثبت شد.'], 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        return response()->json(['data' => $this->customerService->find($request->user(), $id)]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'mobile' => ['nullable', 'string', 'max:20'],
            'national_id' => ['nullable', 'string', 'max:20'],
            'priority' => ['nullable', 'string', 'in:normal,vip'],
            'budget_min' => ['nullable', 'integer', 'min:0'],
            'budget_max' => ['nullable', 'integer', 'min:0'],
            'preferred_type' => ['nullable', 'string', 'max:30'],
            'preferred_city' => ['nullable', 'string', 'max:100'],
            'preferred_district' => ['nullable', 'string', 'max:100'],
            'min_area' => ['nullable', 'integer', 'min:0'],
            'max_area' => ['nullable', 'integer', 'min:0'],
            'min_rooms' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $customer = $this->customerService->update($request->user(), $id, $data);

        return response()->json(['data' => $customer, 'message' => 'مشتری ویرایش شد.']);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $this->customerService->delete($request->user(), $id);

        return response()->json(['message' => 'مشتری حذف شد.']);
    }

    public function matches(Request $request, int $id): JsonResponse
    {
        $matches = $this->customerService->matchProperties($request->user(), $id);

        return response()->json([
            'data' => $matches->map(fn ($m) => [
                'score' => $m['score'],
                'reasons' => $m['reasons'],
                'property' => new PropertyResource($m['property']),
            ]),
        ]);
    }
}
