<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Commission;
use App\Models\CrmDeal;
use App\Models\FeatureFlag;
use App\Models\VirtualTour;
use App\Models\VirtualTourLead;
use App\Services\Admin\AuditLogService;
use App\Services\Admin\OfficeHealthScoreService;
use App\Services\Admin\PlatformDataService;
use App\Services\Sms\IpPanelSmsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminPhase2Controller extends Controller
{
    public function __construct(
        private readonly OfficeHealthScoreService $health,
        private readonly PlatformDataService $platform,
        private readonly AuditLogService $audit,
        private readonly IpPanelSmsService $sms,
    ) {}

    public function healthScores(): JsonResponse
    {
        return response()->json(['data' => $this->health->allOffices()]);
    }

    public function virtualTourStats(): JsonResponse
    {
        if (! class_exists(VirtualTour::class)) {
            return response()->json(['data' => [
                'total_tours' => 0,
                'published_tours' => 0,
                'total_views' => 0,
                'total_leads' => 0,
            ]]);
        }

        return response()->json([
            'data' => [
                'total_tours' => VirtualTour::count(),
                'published_tours' => VirtualTour::published()->count(),
                'draft_tours' => VirtualTour::where('status', 'draft')->count(),
                'total_views' => (int) VirtualTour::sum('view_count'),
                'total_leads' => VirtualTourLead::count(),
                'top_tours' => VirtualTour::published()
                    ->orderByDesc('view_count')
                    ->limit(5)
                    ->get(['id', 'title', 'slug', 'view_count', 'status']),
            ],
        ]);
    }

    public function featureFlags(): JsonResponse
    {
        $this->ensureDefaultFlags();

        return response()->json(['data' => FeatureFlag::orderBy('key')->get()]);
    }

    public function updateFeatureFlag(Request $request, string $key): JsonResponse
    {
        $data = $request->validate([
            'is_enabled' => ['sometimes', 'boolean'],
            'name' => ['sometimes', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $flag = FeatureFlag::firstOrCreate(
            ['key' => $key],
            ['name' => $key, 'is_enabled' => false]
        );

        $flag->update($data);
        $this->audit->log('feature_flag.updated', FeatureFlag::class, $flag->id, "فلگ {$key}");

        return response()->json(['data' => $flag]);
    }

    public function commissionKpi(): JsonResponse
    {
        $months = collect(range(5, 0))->map(function (int $i) {
            $start = now()->subMonths($i)->startOfMonth();
            $end = $start->copy()->endOfMonth();

            $paid = Commission::where('status', 'paid')
                ->whereBetween('paid_at', [$start, $end]);

            $pending = Commission::where('status', 'pending')
                ->whereBetween('created_at', [$start, $end]);

            return [
                'month' => $start->format('Y-m'),
                'label' => $start->locale('fa')->translatedFormat('F Y'),
                'paid_total' => (int) $paid->sum('commission_amount'),
                'paid_count' => $paid->count(),
                'pending_total' => (int) $pending->sum('commission_amount'),
                'pending_count' => $pending->count(),
            ];
        });

        return response()->json([
            'data' => [
                'months' => $months->all(),
                'totals' => [
                    'paid' => (int) Commission::where('status', 'paid')->sum('commission_amount'),
                    'pending' => (int) Commission::where('status', 'pending')->sum('commission_amount'),
                ],
            ],
        ]);
    }

    public function crmFollowUps(Request $request): JsonResponse
    {
        $days = (int) $request->input('days', 7);

        $query = CrmDeal::with(['office:id,name', 'assignee:id,name'])
            ->whereNotIn('stage', ['closed_won', 'closed_lost'])
            ->whereNotNull('follow_up_at')
            ->where('follow_up_at', '<=', now()->addDays($days))
            ->orderBy('follow_up_at');

        $deals = $query->get()->map(fn (CrmDeal $deal) => [
            'id' => $deal->id,
            'title' => $deal->title,
            'contact_name' => $deal->contact_name,
            'contact_mobile' => $deal->contact_mobile,
            'stage' => $deal->stage,
            'follow_up_at' => $deal->follow_up_at?->toIso8601String(),
            'is_overdue' => $deal->follow_up_at?->isPast() ?? false,
            'office' => $deal->office?->only(['id', 'name']),
            'assignee' => $deal->assignee?->only(['id', 'name']),
        ]);

        return response()->json([
            'data' => $deals,
            'meta' => [
                'overdue' => $deals->where('is_overdue', true)->count(),
                'upcoming' => $deals->where('is_overdue', false)->count(),
            ],
        ]);
    }

    public function phase2Summary(): JsonResponse
    {
        $revenue = $this->platform->revenueDetail();

        return response()->json([
            'data' => [
                'mrr_estimate' => $revenue['mrr_estimate'] ?? 0,
                'health' => [
                    'avg_score' => (int) DB::table('office_health_scores')->avg('score'),
                    'low_score_offices' => DB::table('office_health_scores')->where('score', '<', 50)->count(),
                ],
                'virtual_tours' => class_exists(VirtualTour::class) ? [
                    'published' => VirtualTour::published()->count(),
                    'views' => (int) VirtualTour::sum('view_count'),
                    'leads' => VirtualTourLead::count(),
                ] : null,
                'crm_follow_ups_due' => CrmDeal::whereNotIn('stage', ['closed_won', 'closed_lost'])
                    ->whereNotNull('follow_up_at')
                    ->where('follow_up_at', '<=', now())
                    ->count(),
            ],
        ]);
    }

    public function testSms(Request $request): JsonResponse
    {
        $data = $request->validate([
            'mobile' => ['required', 'string', 'regex:/^09\d{9}$/'],
        ]);

        $code = (string) random_int(100000, 999999);
        $result = $this->sms->sendOtp($data['mobile'], $code);

        $this->audit->log('sms.test', null, null, "تست SMS به {$data['mobile']}");

        return response()->json([
            'data' => $result,
            'message' => ($result['success'] ?? false)
                ? 'پیامک تست ارسال شد.'
                : ($result['message'] ?? 'ارسال ناموفق بود.'),
        ], ($result['success'] ?? false) ? 200 : 422);
    }

    private function ensureDefaultFlags(): void
    {
        $defaults = [
            ['key' => 'virtual_tour', 'name' => 'تور مجازی ۳۶۰', 'description' => 'فعال‌سازی ماژول تور مجازی'],
            ['key' => 'crm_advanced', 'name' => 'CRM پیشرفته', 'description' => 'امتیازدهی سرنخ و کانبان'],
            ['key' => 'accounting', 'name' => 'حسابداری', 'description' => 'ماژول حسابداری دفتر'],
            ['key' => 'telegram_bot', 'name' => 'ربات تلگرام', 'description' => 'اتصال ربات تلگرام'],
            ['key' => 'maintenance', 'name' => 'حالت تعمیرات', 'description' => 'نمایش صفحه تعمیرات'],
        ];

        foreach ($defaults as $flag) {
            FeatureFlag::firstOrCreate(
                ['key' => $flag['key']],
                ['name' => $flag['name'], 'description' => $flag['description'], 'is_enabled' => true]
            );
        }
    }
}
