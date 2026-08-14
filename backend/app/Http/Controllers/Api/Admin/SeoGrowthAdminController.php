<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Seo\SeoAlert;
use App\Models\Seo\SeoContentHealth;
use App\Models\Seo\SeoOpportunity;
use App\Models\Seo\SeoRecommendation;
use App\Models\Seo\SeoReportSnapshot;
use App\Models\Seo\SeoSearchQuery;
use App\Models\Seo\SeoTopicScore;
use App\Services\Seo\SeoGscCollector;
use App\Services\Seo\SeoGrowthDashboardService;
use App\Services\Seo\SeoOpportunityEngine;
use App\Services\Seo\SeoRecommendationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SeoGrowthAdminController extends Controller
{
    public function __construct(
        private readonly SeoGrowthDashboardService $dashboard,
        private readonly SeoGscCollector $collector,
        private readonly SeoOpportunityEngine $engine,
        private readonly SeoRecommendationService $recommendations,
    ) {}

    public function executive(): JsonResponse
    {
        return response()->json(['data' => $this->dashboard->executive()]);
    }

    public function collectGsc(): JsonResponse
    {
        $result = $this->collector->collect();

        return response()->json(['data' => $result]);
    }

    public function analyze(): JsonResponse
    {
        return response()->json(['data' => $this->engine->analyze()]);
    }

    public function opportunities(Request $request): JsonResponse
    {
        $q = SeoOpportunity::query()->orderByDesc('priority_score');
        if ($type = $request->string('type')->toString()) {
            $q->where('type', $type);
        }
        if ($status = $request->string('status')->toString()) {
            $q->where('status', $status);
        }

        return response()->json(['data' => $q->limit(100)->get()]);
    }

    public function recommendations(Request $request): JsonResponse
    {
        $q = SeoRecommendation::query()->with('opportunity')->orderByDesc('priority_score');
        if ($status = $request->string('status')->toString()) {
            $q->where('status', $status);
        }

        return response()->json(['data' => $q->limit(100)->get()]);
    }

    public function approveRecommendation(Request $request, int $id): JsonResponse
    {
        $rec = SeoRecommendation::query()->findOrFail($id);

        return response()->json(['data' => $this->recommendations->approve($rec, $request->user())]);
    }

    public function rejectRecommendation(Request $request, int $id): JsonResponse
    {
        $rec = SeoRecommendation::query()->findOrFail($id);
        $notes = $request->string('notes')->toString() ?: null;

        return response()->json(['data' => $this->recommendations->reject($rec, $request->user(), $notes)]);
    }

    public function executeRecommendation(Request $request, int $id): JsonResponse
    {
        $rec = SeoRecommendation::query()->findOrFail($id);

        return response()->json(['data' => $this->recommendations->execute($rec, $request->user())]);
    }

    public function rollbackRecommendation(int $id): JsonResponse
    {
        $rec = SeoRecommendation::query()->findOrFail($id);

        return response()->json(['data' => $this->recommendations->rollback($rec)]);
    }

    public function health(): JsonResponse
    {
        return response()->json([
            'data' => SeoContentHealth::query()
                ->orderByRaw("CASE health WHEN 'critical' THEN 1 WHEN 'needs_update' THEN 2 WHEN 'healthy' THEN 3 ELSE 4 END")
                ->limit(100)->get(),
        ]);
    }

    public function topics(): JsonResponse
    {
        return response()->json(['data' => SeoTopicScore::query()->orderByDesc('topic_score')->get()]);
    }

    public function queries(): JsonResponse
    {
        return response()->json([
            'data' => SeoSearchQuery::query()->orderByDesc('impressions_28d')->limit(100)->get(),
        ]);
    }

    public function alerts(): JsonResponse
    {
        return response()->json([
            'data' => SeoAlert::query()->where('is_resolved', false)->latest()->limit(50)->get(),
        ]);
    }

    public function resolveAlert(int $id): JsonResponse
    {
        $alert = SeoAlert::query()->findOrFail($id);
        $alert->update(['is_resolved' => true, 'resolved_at' => now()]);

        return response()->json(['data' => $alert]);
    }

    public function weeklyReports(): JsonResponse
    {
        return response()->json([
            'data' => SeoReportSnapshot::query()->where('period_type', 'weekly')->latest()->limit(12)->get(),
        ]);
    }
}
