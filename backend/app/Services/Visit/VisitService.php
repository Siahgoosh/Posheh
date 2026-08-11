<?php

namespace App\Services\Visit;

use App\Models\Customer;
use App\Models\Property;
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
        $this->assertOfficeRefs($user, $data);

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
        $this->assertOfficeRefs($user, $data);
        $visit->update($data);

        return $visit->fresh()->load(['property', 'customer', 'assignee']);
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

    private function assertOfficeRefs(User $user, array $data): void
    {
        if (isset($data['property_id'])) {
            $ok = Property::where('office_id', $user->office_id)->where('id', $data['property_id'])->exists();
            if (! $ok) {
                throw ValidationException::withMessages(['property_id' => ['ملک متعلق به دفتر شما نیست.']]);
            }
        }
        if (isset($data['customer_id']) && $data['customer_id']) {
            $ok = Customer::where('office_id', $user->office_id)->where('id', $data['customer_id'])->exists();
            if (! $ok) {
                throw ValidationException::withMessages(['customer_id' => ['مشتری متعلق به دفتر شما نیست.']]);
            }
        }
        if (isset($data['assigned_to']) && $data['assigned_to']) {
            $ok = User::where('office_id', $user->office_id)->where('id', $data['assigned_to'])->exists();
            if (! $ok) {
                throw ValidationException::withMessages(['assigned_to' => ['کاربر تخصیص‌یافته متعلق به دفتر شما نیست.']]);
            }
        }
    }
}
