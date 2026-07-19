<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\DiscountCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DiscountCodeAdminController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => DiscountCode::with('plan')->latest()->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:discount_codes,code'],
            'type' => ['required', 'in:percent,fixed'],
            'value' => ['required', 'integer', 'min:1'],
            'max_uses' => ['nullable', 'integer', 'min:1'],
            'subscription_plan_id' => ['nullable', 'integer', 'exists:subscription_plans,id'],
            'valid_from' => ['nullable', 'date'],
            'valid_until' => ['nullable', 'date', 'after:valid_from'],
            'is_active' => ['boolean'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $data['code'] = strtoupper(trim($data['code']));

        $discount = DiscountCode::create($data);

        return response()->json(['data' => $discount->load('plan')], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $discount = DiscountCode::findOrFail($id);

        $data = $request->validate([
            'code' => ['sometimes', 'string', 'max:50', 'unique:discount_codes,code,'.$id],
            'type' => ['sometimes', 'in:percent,fixed'],
            'value' => ['sometimes', 'integer', 'min:1'],
            'max_uses' => ['nullable', 'integer', 'min:1'],
            'subscription_plan_id' => ['nullable', 'integer', 'exists:subscription_plans,id'],
            'valid_from' => ['nullable', 'date'],
            'valid_until' => ['nullable', 'date'],
            'is_active' => ['boolean'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        if (isset($data['code'])) {
            $data['code'] = strtoupper(trim($data['code']));
        }

        $discount->update($data);

        return response()->json(['data' => $discount->fresh('plan')]);
    }

    public function destroy(int $id): JsonResponse
    {
        DiscountCode::findOrFail($id)->delete();

        return response()->json(['message' => 'کد تخفیف حذف شد.']);
    }
}
