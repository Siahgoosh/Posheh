<?php

namespace App\Services\Accounting;

use App\Models\Commission;
use App\Models\CommissionSplit;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CommissionService
{
    public function list(User $user)
    {
        return Commission::where('office_id', $user->office_id)
            ->with(['property:id,code', 'splits.user:id,name', 'deal:id,title'])
            ->latest()
            ->paginate(20);
    }

    public function createWithSplits(User $user, array $data): Commission
    {
        return DB::transaction(function () use ($user, $data) {
            $commission = Commission::create([
                'office_id' => $user->office_id,
                'property_id' => $data['property_id'] ?? null,
                'deal_id' => $data['deal_id'] ?? null,
                'total_amount' => $data['total_amount'],
                'status' => $data['status'] ?? 'pending',
                'closed_at' => ($data['status'] ?? null) === 'paid' ? now() : null,
            ]);

            $splits = $data['splits'] ?? $this->defaultSplits($user, $data['total_amount']);
            foreach ($splits as $split) {
                CommissionSplit::create([
                    'commission_id' => $commission->id,
                    'user_id' => $split['user_id'],
                    'role' => $split['role'] ?? null,
                    'percentage' => $split['percentage'],
                    'amount' => (int) round($data['total_amount'] * $split['percentage'] / 100),
                ]);
            }

            return $commission->load('splits.user');
        });
    }

    public function summary(User $user): array
    {
        $officeId = $user->office_id;
        $total = Commission::where('office_id', $officeId)->sum('total_amount');
        $paid = Commission::where('office_id', $officeId)->where('status', 'paid')->sum('total_amount');
        $pending = Commission::where('office_id', $officeId)->where('status', 'pending')->sum('total_amount');
        $monthly = Commission::where('office_id', $officeId)
            ->whereMonth('created_at', now()->month)
            ->sum('total_amount');

        return compact('total', 'paid', 'pending', 'monthly');
    }

    private function defaultSplits(User $user, int $total): array
    {
        $team = User::where('office_id', $user->office_id)->where('is_active', true)->get();
        $manager = $team->first(fn ($u) => $u->canManageOffice());
        $consultants = $team->filter(fn ($u) => ! $u->canManageOffice());

        $splits = [];
        if ($manager) {
            $splits[] = ['user_id' => $manager->id, 'role' => 'مدیر', 'percentage' => 30];
        }
        $each = $consultants->count() > 0 ? 70 / $consultants->count() : 70;
        foreach ($consultants as $c) {
            $splits[] = ['user_id' => $c->id, 'role' => 'مشاور', 'percentage' => round($each, 2)];
        }
        if (empty($splits)) {
            $splits[] = ['user_id' => $user->id, 'role' => 'مشاور', 'percentage' => 100];
        }

        return $splits;
    }
}
