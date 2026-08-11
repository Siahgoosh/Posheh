<?php

namespace App\Services\Commission;

use App\Models\Commission;
use App\Models\CommissionSetting;
use App\Models\CrmDeal;
use App\Models\User;
use App\Services\Accounting\SettlementService;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class CommissionService
{
    public function __construct(
        private readonly SettlementService $settlements,
    ) {}

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
            ->when(! $user->canManageOffice(), fn ($q) => $q->where('user_id', $user->id))
            ->latest()
            ->get();
    }

    public function summary(User $user): array
    {
        $base = Commission::where('office_id', $user->office_id)
            ->when(! $user->canManageOffice(), fn ($q) => $q->where('user_id', $user->id));

        return [
            'pending_total' => (int) (clone $base)->where('status', 'pending')->sum('commission_amount'),
            'paid_month' => (int) (clone $base)->where('status', 'paid')
                ->where('paid_at', '>=', now()->startOfMonth())->sum('commission_amount'),
            'pending_count' => (clone $base)->where('status', 'pending')->count(),
        ];
    }

    private function splitAmounts(int $total): array
    {
        $officeShare = (int) round($total * 0.6);
        $consultantShare = $total - $officeShare;

        return [$officeShare, $consultantShare];
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
        $rate = (float) $settings->sale_rate_percent;
        $amount = (int) round($deal->value * $rate / 100);
        [$officeShare, $consultantShare] = $this->splitAmounts($amount);

        $commission = Commission::create([
            'office_id' => $user->office_id,
            'user_id' => $deal->assigned_to,
            'crm_deal_id' => $deal->id,
            'property_id' => $deal->property_id,
            'title' => "کمیسیون — {$deal->title}",
            'base_amount' => $deal->value,
            'rate_percent' => $rate,
            'commission_amount' => $amount,
            'office_share_amount' => $officeShare,
            'consultant_share_amount' => $consultantShare,
            'status' => 'pending',
        ]);

        try {
            $this->settlements->recognizeCommission($user, $commission);
        } catch (\Throwable) {
            // Accounting module may not be migrated yet — commission still saved
        }

        return $commission;
    }

    public function createManual(User $user, array $data): Commission
    {
        if (! $user->canManageOffice()) {
            throw ValidationException::withMessages(['commission' => ['فقط مدیر می‌تواند کمیسیون ثبت کند.']]);
        }

        $assigneeOk = User::where('office_id', $user->office_id)->where('id', $data['user_id'])->exists();
        if (! $assigneeOk) {
            throw ValidationException::withMessages(['user_id' => ['کاربر متعلق به دفتر شما نیست.']]);
        }

        if (! empty($data['property_id'])) {
            $propertyOk = \App\Models\Property::where('office_id', $user->office_id)->where('id', $data['property_id'])->exists();
            if (! $propertyOk) {
                throw ValidationException::withMessages(['property_id' => ['ملک متعلق به دفتر شما نیست.']]);
            }
        }

        $rate = $data['rate_percent'];
        $base = $data['base_amount'];
        $amount = (int) round($base * $rate / 100);
        [$officeShare, $consultantShare] = $this->splitAmounts($amount);

        $commission = Commission::create([
            'office_id' => $user->office_id,
            'user_id' => $data['user_id'],
            'property_id' => $data['property_id'] ?? null,
            'title' => $data['title'],
            'base_amount' => $base,
            'rate_percent' => $rate,
            'commission_amount' => $amount,
            'office_share_amount' => $officeShare,
            'consultant_share_amount' => $consultantShare,
            'status' => 'pending',
            'notes' => $data['notes'] ?? null,
        ]);

        try {
            $this->settlements->recognizeCommission($user, $commission);
        } catch (\Throwable) {
        }

        return $commission;
    }

    public function markPaid(User $user, int $id): Commission
    {
        if (! $user->canManageOffice()) {
            throw ValidationException::withMessages(['commission' => ['فقط مدیر می‌تواند تسویه کند.']]);
        }

        try {
            $this->settlements->settleCommission($user, $id, []);
        } catch (ValidationException $e) {
            // Fallback to legacy flag-only pay if accounting tables missing
            if (str_contains(json_encode($e->errors()), 'حساب')) {
                $commission = Commission::where('office_id', $user->office_id)->findOrFail($id);
                $commission->update(['status' => 'paid', 'paid_at' => now()]);

                return $commission->fresh(['user']);
            }
            throw $e;
        }

        return Commission::where('office_id', $user->office_id)->with('user')->findOrFail($id);
    }
}
