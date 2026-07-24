<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Services\Admin\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminCouponController extends Controller
{
    public function __construct(private readonly AuditLogService $audit) {}

    public function index(): JsonResponse
    {
        return response()->json(['data' => Coupon::latest()->get()]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:coupons,code'],
            'type' => ['required', Rule::in(['percent', 'fixed'])],
            'value' => ['required', 'integer', 'min:1'],
            'max_uses' => ['nullable', 'integer', 'min:1'],
            'plan_slugs' => ['nullable', 'array'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        $coupon = Coupon::create($data);
        $this->audit->log('coupon.created', Coupon::class, $coupon->id, "کوپن {$coupon->code}");

        return response()->json(['data' => $coupon], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $coupon = Coupon::findOrFail($id);
        $data = $request->validate([
            'value' => ['sometimes', 'integer', 'min:1'],
            'max_uses' => ['nullable', 'integer', 'min:1'],
            'ends_at' => ['nullable', 'date'],
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $coupon->update($data);
        $this->audit->log('coupon.updated', Coupon::class, $coupon->id, "ویرایش کوپن {$coupon->code}");

        return response()->json(['data' => $coupon]);
    }

    public function destroy(int $id): JsonResponse
    {
        $coupon = Coupon::findOrFail($id);
        $coupon->delete();
        $this->audit->log('coupon.deleted', Coupon::class, $id, 'حذف کوپن');

        return response()->json(['message' => 'کوپن حذف شد.']);
    }
}
