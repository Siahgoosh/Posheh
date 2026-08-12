<?php

namespace App\Services\Customer;

use App\Models\Customer;
use App\Models\Property;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class CustomerService
{
    public function list(User $user, ?string $q = null, ?string $priority = null): LengthAwarePaginator
    {
        return Customer::where('office_id', $user->office_id)
            ->with('assignee:id,name')
            ->when($q, fn ($query) => $query->where(function ($q2) use ($q) {
                $q2->where('name', 'like', "%{$q}%")
                    ->orWhere('mobile', 'like', "%{$q}%");
            }))
            ->when($priority, fn ($q) => $q->where('priority', $priority))
            ->latest()
            ->paginate(20);
    }

    public function find(User $user, int $id): Customer
    {
        $customer = Customer::where('office_id', $user->office_id)
            ->with(['assignee', 'visits.property'])
            ->find($id);

        if (! $customer) {
            throw ValidationException::withMessages(['customer' => ['مشتری یافت نشد.']]);
        }

        return $customer;
    }

    public function create(User $user, array $data): Customer
    {
        $this->assertAssigneeInOffice($user, $data['assigned_to'] ?? null);

        return Customer::create([
            ...$data,
            'office_id' => $user->office_id,
            'created_by' => $user->id,
            'assigned_to' => $data['assigned_to'] ?? $user->id,
        ]);
    }

    public function update(User $user, int $id, array $data): Customer
    {
        $customer = $this->find($user, $id);
        if (array_key_exists('assigned_to', $data)) {
            $this->assertAssigneeInOffice($user, $data['assigned_to']);
        }
        $customer->update($data);

        return $customer->fresh()->load('assignee');
    }

    public function delete(User $user, int $id): void
    {
        $this->find($user, $id)->delete();
    }

    private function assertAssigneeInOffice(User $user, mixed $assignedTo): void
    {
        if ($assignedTo === null || $assignedTo === '') {
            return;
        }
        $ok = User::where('office_id', $user->office_id)->where('id', (int) $assignedTo)->exists();
        if (! $ok) {
            throw ValidationException::withMessages(['assigned_to' => ['کاربر متعلق به دفتر شما نیست.']]);
        }
    }

    public function matchProperties(User $user, int $customerId, int $limit = 10): Collection
    {
        $customer = $this->find($user, $customerId);

        return app(\App\Services\Crm\PropertyMatchingService::class)
            ->matchForCustomer($user, $customer, $limit);
    }

    public function upsertNeedProfile(User $user, int $customerId, array $data): \App\Models\CrmNeedProfile
    {
        $customer = $this->find($user, $customerId);

        return \App\Models\CrmNeedProfile::updateOrCreate(
            ['customer_id' => $customer->id],
            array_merge($data, ['office_id' => $user->office_id])
        );
    }
}
