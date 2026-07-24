<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Office;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Services\Admin\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminSubscriptionController extends Controller
{
    public function __construct(private readonly AuditLogService $audit) {}

    public function index(Request $request): JsonResponse
    {
        $query = Subscription::with(['office:id,name,slug', 'plan'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('plan_id'), fn ($q) => $q->where('subscription_plan_id', $request->integer('plan_id')))
            ->latest('starts_at');

        return response()->json($query->paginate(20));
    }

    public function extend(Request $request, int $id): JsonResponse
    {
        $subscription = Subscription::with('office')->findOrFail($id);
        $data = $request->validate(['days' => ['required', 'integer', 'min:1', 'max:365']]);

        $oldEnd = $subscription->ends_at?->toDateString();
        $base = $subscription->ends_at && $subscription->ends_at->isFuture()
            ? $subscription->ends_at
            : now();
        $subscription->update([
            'ends_at' => $base->copy()->addDays($data['days']),
            'status' => 'active',
        ]);
        $subscription->office?->update(['plan_active' => true]);

        $this->audit->log(
            'subscription.extended',
            Subscription::class,
            $subscription->id,
            "تمدید {$data['days']} روزه اشتراک",
            ['ends_at' => $oldEnd],
            ['ends_at' => $subscription->ends_at?->toDateString(), 'days' => $data['days']],
        );

        return response()->json(['data' => $subscription->fresh('plan', 'office')]);
    }

    public function assignPlan(Request $request, int $officeId): JsonResponse
    {
        $office = Office::findOrFail($officeId);
        $data = $request->validate([
            'plan_id' => ['required', 'exists:subscription_plans,id'],
            'days' => ['nullable', 'integer', 'min:1', 'max:365'],
        ]);

        $plan = SubscriptionPlan::findOrFail($data['plan_id']);
        $days = $data['days'] ?? 30;

        Subscription::where('office_id', $office->id)->where('status', 'active')->update(['status' => 'expired']);

        $subscription = Subscription::create([
            'office_id' => $office->id,
            'subscription_plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => now(),
            'ends_at' => now()->addDays($days),
            'auto_renew' => false,
        ]);

        $office->update([
            'subscription_plan_id' => $plan->id,
            'panel_type' => $plan->panel_type,
            'plan_active' => true,
        ]);

        $this->audit->log('subscription.assigned', Office::class, $office->id, "اختصاص پلن {$plan->name}", null, [
            'plan_id' => $plan->id,
            'days' => $days,
        ]);

        return response()->json(['data' => $subscription->load('plan')]);
    }
}
