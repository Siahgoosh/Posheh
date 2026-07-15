<?php

namespace App\Services\Commission;

use App\Models\Commission;
use App\Models\CommissionSetting;
use App\Models\CrmDeal;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class CommissionService
{
    public function getSettings(User $user): CommissionSetting
    {
        return CommissionSetting::firstOrCreate(
            ['office_id' => $user->office_id],
            ['sale_rate_percent' => 30, 'rent_rate_percent' => 50]
        );
    }

    public function updateSettings(User $user, array $data): CommissionSetting
    {
        if (! $user->canManageOffice()) {
            throw ValidationException::withMessages(['commission' => ['فقط مدیر دفتر می‌تواند تنظیمات کمیسیون را تغییر دهد.']]);
        }

        $settings = $this->getSettings($user);
        $settings->update($data);

        return $settings;
    }

    public function list(User $user, ?string $status = null): Collection
    {
        return Commission::where('office_id', $user->office_id)
            ->with(['user:id,name', 'deal:id,title', 'property:id,code'])
            ->when($status, fn ($q) => $q->where('status', $status))
            ->latest()
            ->get();
    }

    public function summary(User $user): array
    {
        $base = Commission::where('office_id', $user->office_id);

        return [
            'pending_total' => (int) (clone $base)->where('status', 'pending')->sum('commission_amount'),
            'paid_month' => (int) (clone $base)->where('status', 'paid')
                ->where('paid_at', '>=', now()->startOfMonth())->sum('commission_amount'),
            'pending_count' => (clone $base)->where('status', 'pending')->count(),
        ];
    }

    public function createFromDeal(User $user, CrmDeal $deal): ?Commission
    {
        if ($deal->stage !== 'closed_won' || ! $deal->value || ! $deal->assigned_to) {
            return null;
        }

        if (Commission::where('crm_deal_id', $deal->id)->exists()) {
            return null;
        }

        $settings = $this->getSettings($user);
        $rate = $settings->sale_rate_percent;

        return Commission::create([
            'office_id' => $user->office_id,
            'user_id' => $deal->assigned_to,
            'crm_deal_id' => $deal->id,
            'property_id' => $deal->property_id,
            'title' => "کمیسیون — {$deal->title}",
            'base_amount' => $deal->value,
            'rate_percent' => $rate,
            'commission_amount' => (int) round($deal->value * $rate / 100),
            'status' => 'pending',
        ]);
    }

    public function createManual(User $user, array $data): Commission
    {
        $rate = $data['rate_percent'];
        $base = $data['base_amount'];

        return Commission::create([
            'office_id' => $user->office_id,
            'user_id' => $data['user_id'],
            'property_id' => $data['property_id'] ?? null,
            'title' => $data['title'],
            'base_amount' => $base,
            'rate_percent' => $rate,
            'commission_amount' => (int) round($base * $rate / 100),
            'status' => 'pending',
            'notes' => $data['notes'] ?? null,
        ]);
    }

    public function markPaid(User $user, int $id): Commission
    {
        if (! $user->canManageOffice()) {
            throw ValidationException::withMessages(['commission' => ['فقط مدیر می‌تواند تسویه کند.']]);
        }

        $commission = Commission::where('office_id', $user->office_id)->findOrFail($id);
        $commission->update(['status' => 'paid', 'paid_at' => now()]);

        return $commission->fresh(['user']);
    }
}
