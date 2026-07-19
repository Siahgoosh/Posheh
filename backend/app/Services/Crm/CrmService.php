<?php

namespace App\Services\Crm;

use App\Models\CrmActivity;
use App\Models\CrmDeal;
use App\Models\User;
use App\Services\Commission\CommissionService;

class CrmService
{
    public const STAGES = ['lead', 'contact', 'visit', 'negotiation', 'closed_won', 'closed_lost'];

    public const STAGE_LABELS = [
        'lead' => 'سرنخ',
        'contact' => 'تماس',
        'visit' => 'بازدید',
        'negotiation' => 'مذاکره',
        'closed_won' => 'موفق',
        'closed_lost' => 'ناموفق',
    ];

    public const PRIORITIES = ['low', 'medium', 'high', 'urgent'];

    public function __construct(
        private readonly CommissionService $commissionService,
    ) {}

    public function list(User $user)
    {
        return CrmDeal::with(['assignee', 'property'])
            ->where('office_id', $user->office_id)
            ->orderByDesc('updated_at')
            ->get()
            ->map(fn (CrmDeal $deal) => $this->enrichDeal($deal));
    }

    public function create(User $user, array $data): CrmDeal
    {
        $deal = CrmDeal::create(array_merge($data, [
            'office_id' => $user->office_id,
            'assigned_to' => $data['assigned_to'] ?? $user->id,
            'lead_score' => $data['lead_score'] ?? $this->calculateLeadScore($data),
        ]));

        $this->logActivity($user, $deal, 'created', 'معامله ایجاد شد');

        return $this->enrichDeal($deal->load(['assignee', 'property']));
    }

    public function update(User $user, int $id, array $data): CrmDeal
    {
        $deal = CrmDeal::where('office_id', $user->office_id)->findOrFail($id);
        $oldStage = $deal->stage;
        $deal->update($data);
        $deal = $deal->fresh(['assignee', 'property']);

        if (isset($data['stage']) && $data['stage'] !== $oldStage) {
            $fromLabel = self::STAGE_LABELS[$oldStage] ?? $oldStage;
            $toLabel = self::STAGE_LABELS[$data['stage']] ?? $data['stage'];
            $this->logActivity($user, $deal, 'stage_change', "مرحله از «{$fromLabel}» به «{$toLabel}» تغییر کرد", [
                'from' => $oldStage,
                'to' => $data['stage'],
                'from_label' => $fromLabel,
                'to_label' => $toLabel,
            ]);
        }

        if ($deal->stage === 'closed_won') {
            $this->commissionService->createFromDeal($user, $deal);
        }

        return $this->enrichDeal($deal);
    }

    public function stages(): array
    {
        return array_map(
            fn (string $key) => ['key' => $key, 'label' => self::STAGE_LABELS[$key] ?? $key],
            self::STAGES,
        );
    }

    public static function stageLabel(?string $stage): string
    {
        return self::STAGE_LABELS[$stage ?? ''] ?? ($stage ?? '');
    }

    public function pipelineSummary(User $user): array
    {
        return CrmDeal::where('office_id', $user->office_id)
            ->selectRaw('stage, count(*) as count, sum(value) as total_value')
            ->groupBy('stage')
            ->get()
            ->keyBy('stage')
            ->toArray();
    }

    public function followUps(User $user)
    {
        return CrmDeal::with(['assignee'])
            ->where('office_id', $user->office_id)
            ->whereNotNull('follow_up_at')
            ->where('follow_up_at', '<=', now()->addDays(7))
            ->whereNotIn('stage', ['closed_won', 'closed_lost'])
            ->orderBy('follow_up_at')
            ->get()
            ->map(fn (CrmDeal $deal) => $this->enrichDeal($deal));
    }

    public function activities(User $user, int $dealId)
    {
        $deal = CrmDeal::where('office_id', $user->office_id)->findOrFail($dealId);

        return $deal->activities()->with('user')->limit(50)->get();
    }

    public function addActivity(User $user, int $dealId, array $data): CrmActivity
    {
        $deal = CrmDeal::where('office_id', $user->office_id)->findOrFail($dealId);

        return $this->logActivity($user, $deal, $data['type'] ?? 'note', $data['body'] ?? '', $data['meta'] ?? null);
    }

    public function calculateLeadScore(array $data): int
    {
        $score = 30;

        if (! empty($data['contact_mobile'])) {
            $score += 20;
        }
        if (! empty($data['contact_name'])) {
            $score += 10;
        }
        if (! empty($data['value']) && $data['value'] > 0) {
            $score += 15;
        }
        if (! empty($data['property_id'])) {
            $score += 15;
        }
        if (! empty($data['source'])) {
            $score += 10;
        }

        return min(100, $score);
    }

    private function enrichDeal(CrmDeal $deal): CrmDeal
    {
        $deal->setAttribute('is_overdue', $deal->follow_up_at && $deal->follow_up_at->isPast()
            && ! in_array($deal->stage, ['closed_won', 'closed_lost'], true));
        $deal->setAttribute('stage_label', self::STAGE_LABELS[$deal->stage] ?? $deal->stage);

        return $deal;
    }

    private function logActivity(User $user, CrmDeal $deal, string $type, ?string $body = null, ?array $meta = null): CrmActivity
    {
        return CrmActivity::create([
            'crm_deal_id' => $deal->id,
            'user_id' => $user->id,
            'type' => $type,
            'body' => $body,
            'meta' => $meta,
        ]);
    }
}
