<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Office;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Services\Admin\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

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
        try {
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

            if ($subscription->office && Schema::hasColumn('offices', 'plan_active')) {
                $subscription->office->update(['plan_active' => true]);
            }

            $this->audit->log(
                'subscription.extended',
                Subscription::class,
                $subscription->id,
                "تمدید {$data['days']} روزه اشتراک",
                ['ends_at' => $oldEnd],
                ['ends_at' => $subscription->ends_at?->toDateString(), 'days' => $data['days']],
            );

            return response()->json(['data' => $subscription->fresh('plan', 'office')]);
        } catch (Throwable $e) {
            return $this->fail('تمدید اشتراک', $e);
        }
    }

    public function assignPlan(Request $request, int $officeId): JsonResponse
    {
        try {
            if (! Schema::hasTable('subscriptions') || ! Schema::hasTable('subscription_plans')) {
                return response()->json([
                    'message' => 'جداول اشتراک در دیتابیس وجود ندارد. لطفاً migrate را اجرا کنید.',
                    'code' => 'schema_outdated',
                ], 500);
            }

            $office = Office::findOrFail($officeId);
            $data = $request->validate([
                'plan_id' => ['required', 'integer', 'exists:subscription_plans,id'],
                'days' => ['nullable', 'integer', 'min:1', 'max:3650'],
            ]);

            $plan = SubscriptionPlan::findOrFail($data['plan_id']);
            if (Schema::hasColumn('subscription_plans', 'is_active') && ! $plan->is_active) {
                return response()->json(['message' => 'این پلن غیرفعال است.'], 422);
            }

            $days = $data['days'] ?? max(1, (int) ($plan->trial_days ?: 30));

            $subscription = DB::transaction(function () use ($office, $plan, $days) {
                Subscription::where('office_id', $office->id)->where('status', 'active')->update(['status' => 'expired']);

                $subscription = Subscription::create([
                    'office_id' => $office->id,
                    'subscription_plan_id' => $plan->id,
                    'status' => 'active',
                    'starts_at' => now(),
                    'ends_at' => now()->addDays($days),
                    'auto_renew' => false,
                ]);

                $officePatch = [];
                if (Schema::hasColumn('offices', 'subscription_plan_id')) {
                    $officePatch['subscription_plan_id'] = $plan->id;
                }
                if (Schema::hasColumn('offices', 'panel_type')) {
                    $officePatch['panel_type'] = $plan->panel_type;
                }
                if (Schema::hasColumn('offices', 'plan_active')) {
                    $officePatch['plan_active'] = true;
                }
                if ($officePatch !== []) {
                    $office->update($officePatch);
                }

                return $subscription;
            });

            $this->audit->log('subscription.assigned', Office::class, $office->id, "اختصاص پلن {$plan->name}", null, [
                'plan_id' => $plan->id,
                'days' => $days,
            ]);

            return response()->json([
                'data' => $subscription->load('plan'),
                'message' => "پلن «{$plan->name}» برای {$days} روز تخصیص داده شد.",
            ]);
        } catch (Throwable $e) {
            return $this->fail('تخصیص پلن', $e);
        }
    }

    private function fail(string $op, Throwable $e): JsonResponse
    {
        Log::error("admin.{$op}.failed", [
            'error' => $e->getMessage(),
            'class' => $e::class,
        ]);

        $hint = $this->schemaHint($e->getMessage());

        return response()->json([
            'message' => $hint ?: ("خطا در عملیات {$op}: ".$e->getMessage()),
            'code' => $hint ? 'schema_outdated' : class_basename($e),
            'error' => $e->getMessage(),
        ], 500);
    }

    private function schemaHint(string $message): ?string
    {
        $m = mb_strtolower($message);
        if (str_contains($m, 'audit_logs') || str_contains($m, 'unknown column') || str_contains($m, 'base table or view not found')) {
            return 'اسکیمای دیتابیس قدیمی است. روی سرور اجرا کنید: ./scripts/deploy.sh cursor/production-release-a876';
        }

        return null;
    }
}
