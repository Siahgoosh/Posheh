<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Coupon;
use App\Models\FeatureFlag;
use App\Models\Office;
use App\Models\OfficeHealthScore;
use App\Models\Payment;
use App\Models\Ticket;
use App\Models\User;
use App\Models\VirtualTour;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminExtendedController extends Controller
{
    public function healthScores(): JsonResponse
    {
        $offices = Office::with(['healthScore', 'subscription.plan'])
            ->withCount(['properties', 'users'])
            ->get()
            ->map(function (Office $office) {
                $score = $this->calculateHealth($office);
                OfficeHealthScore::updateOrCreate(
                    ['office_id' => $office->id],
                    ['score' => $score['total'], 'factors' => $score['factors'], 'calculated_at' => now()]
                );

                return [
                    'id' => $office->id,
                    'name' => $office->name,
                    'is_active' => $office->is_active,
                    'properties_count' => $office->properties_count,
                    'users_count' => $office->users_count,
                    'health_score' => $score['total'],
                    'factors' => $score['factors'],
                    'plan' => $office->subscription?->plan?->name,
                ];
            });

        return response()->json(['data' => $offices]);
    }

    public function toggleOffice(Request $request, int $id): JsonResponse
    {
        $office = Office::findOrFail($id);
        $office->update(['is_active' => ! $office->is_active]);

        return response()->json(['data' => $office, 'message' => $office->is_active ? 'دفتر فعال شد.' : 'دفتر تعلیق شد.']);
    }

    public function mrr(): JsonResponse
    {
        $mrr = Payment::where('status', 'paid')
            ->where('created_at', '>=', now()->subMonth())
            ->sum('amount');

        $arr = $mrr * 12;
        $trialOffices = Office::whereDoesntHave('subscription', fn ($q) => $q->where('status', 'active'))->count();
        $paidOffices = Office::whereHas('subscription', fn ($q) => $q->where('status', 'active'))->count();

        return response()->json([
            'data' => compact('mrr', 'arr', 'trialOffices', 'paidOffices'),
        ]);
    }

    public function coupons(): JsonResponse
    {
        return response()->json(['data' => Coupon::latest()->get()]);
    }

    public function createCoupon(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'unique:coupons,code'],
            'discount_percent' => ['required', 'integer', 'min:1', 'max:100'],
            'plan_slug' => ['nullable', 'string'],
            'max_uses' => ['nullable', 'integer'],
            'valid_until' => ['nullable', 'date'],
        ]);

        $coupon = Coupon::create($data);

        return response()->json(['data' => $coupon], 201);
    }

    public function featureFlags(): JsonResponse
    {
        return response()->json(['data' => FeatureFlag::all()]);
    }

    public function setFeatureFlag(Request $request): JsonResponse
    {
        $data = $request->validate([
            'key' => ['required', 'string'],
            'enabled' => ['required', 'boolean'],
            'name' => ['nullable', 'string'],
        ]);

        $flag = FeatureFlag::updateOrCreate(
            ['key' => $data['key']],
            [
                'name' => $data['name'] ?? $data['key'],
                'is_enabled' => $data['enabled'],
            ]
        );

        return response()->json(['data' => $flag]);
    }

    public function auditLogs(Request $request): JsonResponse
    {
        $logs = AuditLog::with(['user:id,name', 'office:id,name'])
            ->latest()
            ->paginate(50);

        return response()->json($logs);
    }

    public function virtualTourStats(): JsonResponse
    {
        return response()->json([
            'data' => [
                'total_tours' => VirtualTour::count(),
                'published_tours' => VirtualTour::published()->count(),
                'total_views' => VirtualTour::sum('view_count'),
                'total_leads' => DB::table('virtual_tour_leads')->count(),
            ],
        ]);
    }

    private function calculateHealth(Office $office): array
    {
        $factors = [
            'active' => $office->is_active ? 25 : 0,
            'has_subscription' => $office->subscription?->status === 'active' ? 25 : 5,
            'properties' => min(25, ($office->properties_count ?? 0) * 2),
            'users' => min(25, ($office->users_count ?? 0) * 5),
        ];

        return ['total' => array_sum($factors), 'factors' => $factors];
    }
}
