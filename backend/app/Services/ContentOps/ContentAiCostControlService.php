<?php

namespace App\Services\ContentOps;

use App\Models\AiUsageLog;
use App\Models\Content\ContentAiCostLimit;
use App\Models\Content\ContentAiJob;
use Illuminate\Support\Facades\Schema;

class ContentAiCostControlService
{
    public function estimateCostToman(int $promptTokens, int $completionTokens): int
    {
        $per1k = (int) config('content_ops.token_cost_toman_per_1k', 0);
        if ($per1k <= 0) {
            return 0;
        }

        return (int) ceil((($promptTokens + $completionTokens) / 1000) * $per1k);
    }

    /** @return array{allowed:bool,reason:?string,spent_toman:int,limit_toman:int} */
    public function checkLimits(?int $userId = null, ?int $blogPostId = null, int $estimatedCost = 0): array
    {
        if (! Schema::hasTable('content_ai_cost_limits')) {
            return ['allowed' => true, 'reason' => null, 'spent_toman' => 0, 'limit_toman' => 0];
        }

        $checks = [
            'daily' => [now()->startOfDay(), now()->endOfDay()],
            'monthly' => [now()->startOfMonth(), now()->endOfMonth()],
        ];

        foreach ($checks as $scope => [$from, $to]) {
            $limit = ContentAiCostLimit::query()->where('scope', $scope)->where('is_active', true)->first();
            if (! $limit || (int) $limit->limit_toman <= 0) {
                continue;
            }
            $spent = (int) ContentAiJob::query()
                ->whereBetween('created_at', [$from, $to])
                ->whereIn('status', ['completed', 'running', 'queued', 'retrying'])
                ->sum('actual_cost_toman');
            if ($spent + $estimatedCost > (int) $limit->limit_toman) {
                return [
                    'allowed' => false,
                    'reason' => "Cost limit exceeded for scope={$scope}",
                    'spent_toman' => $spent,
                    'limit_toman' => (int) $limit->limit_toman,
                ];
            }
        }

        if ($userId) {
            $limit = ContentAiCostLimit::query()->where('scope', 'per_user')->where('is_active', true)->first();
            if ($limit && (int) $limit->limit_toman > 0) {
                $spent = (int) ContentAiJob::query()
                    ->where('created_by', $userId)
                    ->whereBetween('created_at', [now()->startOfDay(), now()->endOfDay()])
                    ->sum('actual_cost_toman');
                if ($spent + $estimatedCost > (int) $limit->limit_toman) {
                    return [
                        'allowed' => false,
                        'reason' => 'Per-user daily cost limit exceeded',
                        'spent_toman' => $spent,
                        'limit_toman' => (int) $limit->limit_toman,
                    ];
                }
            }
        }

        if ($blogPostId) {
            $limit = ContentAiCostLimit::query()->where('scope', 'per_article')->where('is_active', true)->first();
            if ($limit && (int) $limit->limit_toman > 0) {
                $spent = (int) ContentAiJob::query()
                    ->where('blog_post_id', $blogPostId)
                    ->sum('actual_cost_toman');
                if ($spent + $estimatedCost > (int) $limit->limit_toman) {
                    return [
                        'allowed' => false,
                        'reason' => 'Per-article cost limit exceeded',
                        'spent_toman' => $spent,
                        'limit_toman' => (int) $limit->limit_toman,
                    ];
                }
            }
        }

        return ['allowed' => true, 'reason' => null, 'spent_toman' => 0, 'limit_toman' => 0];
    }

    public function logUsage(?int $userId, string $feature, ContentAiJob $job): void
    {
        if (! Schema::hasTable('ai_usage_logs')) {
            return;
        }
        AiUsageLog::create([
            'office_id' => null,
            'user_id' => $userId,
            'feature' => 'content_ops.'.$feature,
            'provider' => $job->provider,
            'model' => $job->model,
            'prompt_tokens' => $job->prompt_tokens,
            'completion_tokens' => $job->completion_tokens,
            'total_tokens' => $job->total_tokens,
            'cost_toman' => $job->actual_cost_toman,
            'status' => $job->status === 'completed' ? 'ok' : ($job->status === 'failed' ? 'error' : $job->status),
            'meta' => [
                'job_id' => $job->id,
                'type' => $job->type,
                'blog_post_id' => $job->blog_post_id,
                // never store secrets
            ],
        ]);
    }

    /** @return array<string,mixed> */
    public function usageSummary(): array
    {
        if (! Schema::hasTable('content_ai_jobs')) {
            return ['note' => 'Migration required'];
        }

        $base = ContentAiJob::query();
        $month = ContentAiJob::query()->where('created_at', '>=', now()->startOfMonth());

        $byType = ContentAiJob::query()
            ->selectRaw('type, count(*) as c')
            ->groupBy('type')
            ->orderByDesc('c')
            ->limit(10)
            ->pluck('c', 'type');

        $completed = (clone $month)->where('status', 'completed')->count();
        $failed = (clone $month)->where('status', 'failed')->count();
        $tokens = (int) (clone $month)->sum('total_tokens');
        $cost = (int) (clone $month)->sum('actual_cost_toman');
        $avgDuration = (int) round((float) (clone $month)->whereNotNull('duration_ms')->avg('duration_ms'));

        return [
            'total_jobs' => (clone $base)->count(),
            'month' => [
                'jobs' => (clone $month)->count(),
                'successful' => $completed,
                'failed' => $failed,
                'tokens' => $tokens,
                'cost_toman' => $cost,
                'avg_cost_toman' => $completed > 0 ? (int) round($cost / $completed) : 0,
                'avg_duration_ms' => $avgDuration,
            ],
            'top_tasks' => $byType,
            'note' => 'Costs are estimated/actual from Content OS jobs — not a Google metric.',
        ];
    }
}
