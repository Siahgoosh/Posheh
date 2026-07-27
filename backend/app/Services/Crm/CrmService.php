<?php

namespace App\Services\Crm;

use App\Models\CrmDeal;
use App\Models\CrmStage;
use App\Models\User;

class CrmService
{
    public function ensureDefaultStages(int $officeId): void
    {
        if (CrmStage::where('office_id', $officeId)->exists()) {
            return;
        }

        $defaults = [
            ['name' => 'سرنخ جدید', 'color' => '#6366f1', 'sort_order' => 0],
            ['name' => 'تماس اول', 'color' => '#8b5cf6', 'sort_order' => 1],
            ['name' => 'بازدید', 'color' => '#0ea5e9', 'sort_order' => 2],
            ['name' => 'مذاکره', 'color' => '#f59e0b', 'sort_order' => 3],
            ['name' => 'قرارداد', 'color' => '#22c55e', 'sort_order' => 4, 'is_won' => true],
            ['name' => 'از دست رفته', 'color' => '#ef4444', 'sort_order' => 5, 'is_lost' => true],
        ];

        foreach ($defaults as $stage) {
            CrmStage::create(['office_id' => $officeId, ...$stage]);
        }
    }

    public function pipeline(User $user): array
    {
        $this->ensureDefaultStages($user->office_id);

        $stages = CrmStage::where('office_id', $user->office_id)
            ->orderBy('sort_order')
            ->with(['deals' => fn ($q) => $q->with(['property:id,code', 'assignee:id,name'])->orderBy('sort_order')])
            ->get();

        return $stages->toArray();
    }

    public function createDeal(User $user, array $data): CrmDeal
    {
        $this->ensureDefaultStages($user->office_id);
        $stageId = $data['stage_id'] ?? CrmStage::where('office_id', $user->office_id)->orderBy('sort_order')->value('id');

        return CrmDeal::create([
            'office_id' => $user->office_id,
            'stage_id' => $stageId,
            'property_id' => $data['property_id'] ?? null,
            'assigned_to' => $data['assigned_to'] ?? $user->id,
            'created_by' => $user->id,
            'title' => $data['title'],
            'customer_name' => $data['customer_name'] ?? null,
            'customer_mobile' => $data['customer_mobile'] ?? null,
            'value' => $data['value'] ?? null,
            'lead_score' => $this->calculateLeadScore($data),
            'notes' => $data['notes'] ?? null,
            'next_follow_up_at' => $data['next_follow_up_at'] ?? null,
            'sort_order' => CrmDeal::where('stage_id', $stageId)->count(),
        ]);
    }

    public function moveDeal(User $user, int $dealId, int $stageId, int $sortOrder = 0): CrmDeal
    {
        $deal = CrmDeal::where('office_id', $user->office_id)->findOrFail($dealId);
        $deal->update(['stage_id' => $stageId, 'sort_order' => $sortOrder]);

        return $deal->fresh(['property', 'assignee', 'stage']);
    }

    public function updateDeal(User $user, int $dealId, array $data): CrmDeal
    {
        $deal = CrmDeal::where('office_id', $user->office_id)->findOrFail($dealId);
        if (isset($data['customer_name']) || isset($data['value'])) {
            $data['lead_score'] = $this->calculateLeadScore(array_merge($deal->toArray(), $data));
        }
        $deal->update($data);

        return $deal->fresh(['property', 'assignee', 'stage']);
    }

    private function calculateLeadScore(array $data): int
    {
        $score = 0;
        if (! empty($data['customer_mobile'])) {
            $score += 20;
        }
        if (! empty($data['value']) && $data['value'] > 0) {
            $score += min(30, (int) ($data['value'] / 1_000_000_000));
        }
        if (! empty($data['property_id'])) {
            $score += 25;
        }
        if (! empty($data['next_follow_up_at'])) {
            $score += 10;
        }
        if (! empty($data['notes'])) {
            $score += 5;
        }

        return min(100, $score);
    }
}
