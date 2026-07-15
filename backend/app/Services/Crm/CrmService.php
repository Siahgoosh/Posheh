<?php

namespace App\Services\Crm;

use App\Models\CrmDeal;
use App\Models\User;
use App\Services\Commission\CommissionService;

class CrmService
{
    public const STAGES = ['lead', 'contact', 'visit', 'negotiation', 'closed_won', 'closed_lost'];

    public function __construct(
        private readonly CommissionService $commissionService,
    ) {}

    public function list(User $user)
    {
        return CrmDeal::with(['assignee', 'property'])
            ->where('office_id', $user->office_id)
            ->orderByDesc('updated_at')
            ->get();
    }

    public function create(User $user, array $data): CrmDeal
    {
        return CrmDeal::create(array_merge($data, [
            'office_id' => $user->office_id,
            'assigned_to' => $data['assigned_to'] ?? $user->id,
        ]));
    }

    public function update(User $user, int $id, array $data): CrmDeal
    {
        $deal = CrmDeal::where('office_id', $user->office_id)->findOrFail($id);
        $deal->update($data);
        $deal = $deal->fresh(['assignee', 'property']);

        if ($deal->stage === 'closed_won') {
            $this->commissionService->createFromDeal($user, $deal);
        }

        return $deal;
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
}
