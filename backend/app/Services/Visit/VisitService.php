<?php

namespace App\Services\Visit;

use App\Models\PropertyVisit;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Morilog\Jalali\Jalalian;

class VisitService
{
    public function listForMonth(User $user, int $year, int $month): Collection
    {
        $start = Jalalian::fromFormat('Y/m/d', "{$year}/{$month}/1")->toCarbon()->startOfDay();
        $end = (clone $start)->endOfMonth();

        return PropertyVisit::where('office_id', $user->office_id)
            ->whereBetween('visit_at', [$start, $end])
            ->with(['property:id,code,city,district', 'customer:id,name,mobile', 'assignee:id,name'])
            ->orderBy('visit_at')
            ->get();
    }

    public function upcoming(User $user, int $days = 7): Collection
    {
        return PropertyVisit::where('office_id', $user->office_id)
            ->where('status', 'scheduled')
            ->whereBetween('visit_at', [now(), now()->addDays($days)])
            ->with(['property', 'customer'])
            ->orderBy('visit_at')
            ->get();
    }

    public function create(User $user, array $data): PropertyVisit
    {
        $this->assertNoDoubleBooking($user, $data);

        return PropertyVisit::create([
            ...$data,
            'office_id' => $user->office_id,
            'created_by' => $user->id,
            'assigned_to' => $data['assigned_to'] ?? $user->id,
        ])->load(['property', 'customer', 'assignee']);
    }

    public function update(User $user, int $id, array $data): PropertyVisit
    {
        $visit = $this->find($user, $id);
        if (isset($data['visit_at']) || isset($data['assigned_to']) || isset($data['duration_minutes'])) {
            $this->assertNoDoubleBooking($user, [
                'visit_at' => $data['visit_at'] ?? $visit->visit_at,
                'assigned_to' => $data['assigned_to'] ?? $visit->assigned_to,
                'duration_minutes' => $data['duration_minutes'] ?? $visit->duration_minutes,
            ], $visit->id);
        }
        $visit->update($data);

        return $visit->fresh()->load(['property', 'customer', 'assignee']);
    }

    private function assertNoDoubleBooking(User $user, array $data, ?int $ignoreId = null): void
    {
        $start = \Carbon\Carbon::parse($data['visit_at'] ?? now());
        $duration = (int) ($data['duration_minutes'] ?? 30);
        $end = (clone $start)->addMinutes($duration);
        $assignee = $data['assigned_to'] ?? $user->id;

        // Portable overlap check (works on MySQL + SQLite): existing.start < new.end AND existing.end > new.start
        $candidates = PropertyVisit::where('office_id', $user->office_id)
            ->where('assigned_to', $assignee)
            ->where('status', 'scheduled')
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->where('visit_at', '<', $end)
            ->where('visit_at', '>=', (clone $start)->subHours(6))
            ->get(['id', 'visit_at', 'duration_minutes']);

        foreach ($candidates as $existing) {
            $existingStart = \Carbon\Carbon::parse($existing->visit_at);
            $existingEnd = (clone $existingStart)->addMinutes((int) ($existing->duration_minutes ?? 30));
            if ($existingStart->lt($end) && $existingEnd->gt($start)) {
                throw ValidationException::withMessages([
                    'visit_at' => ['تداخل زمانی با بازدید دیگری برای این مشاور وجود دارد.'],
                ]);
            }
        }
    }

    public function complete(User $user, int $id, array $feedback = []): PropertyVisit
    {
        $visit = $this->find($user, $id);
        $visit->update(array_merge([
            'status' => 'completed',
        ], array_filter([
            'customer_reaction' => $feedback['customer_reaction'] ?? null,
            'property_rating' => $feedback['property_rating'] ?? null,
            'price_opinion' => $feedback['price_opinion'] ?? null,
            'likelihood_to_buy' => $feedback['likelihood_to_buy'] ?? null,
            'next_action' => $feedback['next_action'] ?? null,
            'notes' => $feedback['notes'] ?? null,
        ], fn ($v) => $v !== null)));

        $visit = $visit->fresh()->load(['property', 'customer', 'assignee']);

        try {
            $deal = null;
            if ($visit->crm_deal_id) {
                $deal = \App\Models\CrmDeal::where('office_id', $user->office_id)->find($visit->crm_deal_id);
            }
            app(\App\Services\Crm\CrmAutomationService::class)
                ->dispatch($user, 'visit_completed', $deal ?? $visit, [
                    'visit_id' => $visit->id,
                    'likelihood_to_buy' => $visit->likelihood_to_buy,
                ]);
        } catch (\Throwable) {
            // Automation optional if tables not migrated
        }

        return $visit;
    }

    public function delete(User $user, int $id): void
    {
        $this->find($user, $id)->delete();
    }

    public function find(User $user, int $id): PropertyVisit
    {
        $visit = PropertyVisit::where('office_id', $user->office_id)->find($id);

        if (! $visit) {
            throw ValidationException::withMessages(['visit' => ['بازدید یافت نشد.']]);
        }

        return $visit;
    }
}
