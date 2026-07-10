<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PlanAdminController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => SubscriptionPlan::orderBy('sort_order')->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);
        $plan = SubscriptionPlan::create($data);

        return response()->json(['data' => $plan], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $plan = SubscriptionPlan::findOrFail($id);
        $plan->update($this->validated($request, $plan));

        return response()->json(['data' => $plan->fresh()]);
    }

    public function destroy(int $id): JsonResponse
    {
        $plan = SubscriptionPlan::findOrFail($id);
        $plan->update(['is_active' => false]);

        return response()->json(['message' => 'پلن غیرفعال شد.']);
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, ?SubscriptionPlan $existing = null): array
    {
        return $request->validate([
            'slug' => [
                'sometimes',
                'string',
                'max:50',
                Rule::unique('subscription_plans', 'slug')->ignore($existing?->id),
            ],
            'panel_type' => ['sometimes', 'string', Rule::in(['solo', 'office', 'premium'])],
            'name' => ['sometimes', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'max_users' => ['sometimes', 'integer', 'min:1'],
            'max_properties' => ['sometimes', 'integer', 'min:1'],
            'storage_gb' => ['sometimes', 'integer', 'min:1'],
            'monthly_price' => ['sometimes', 'integer', 'min:0'],
            'yearly_price' => ['nullable', 'integer', 'min:0'],
            'features' => ['nullable', 'array'],
            'features.*' => ['string'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer'],
            'trial_days' => ['sometimes', 'integer', 'min:0', 'max:30'],
        ]);
    }
}
