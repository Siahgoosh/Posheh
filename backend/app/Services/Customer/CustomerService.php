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
        $customer->update($data);

        return $customer->fresh()->load('assignee');
    }

    public function delete(User $user, int $id): void
    {
        $this->find($user, $id)->delete();
    }

    public function matchProperties(User $user, int $customerId, int $limit = 10): Collection
    {
        $customer = $this->find($user, $customerId);

        $properties = Property::where('office_id', $user->office_id)
            ->where('status', 'active')
            ->with('media')
            ->get();

        return $properties
            ->map(function (Property $property) use ($customer) {
                $score = 0;
                $reasons = [];

                if ($customer->preferred_type && $property->type?->value === $customer->preferred_type) {
                    $score += 25;
                    $reasons[] = 'نوع معامله';
                }
                if ($customer->preferred_city && $property->city && str_contains($property->city, $customer->preferred_city)) {
                    $score += 20;
                    $reasons[] = 'شهر';
                }
                if ($customer->preferred_district && $property->district && str_contains($property->district, $customer->preferred_district)) {
                    $score += 15;
                    $reasons[] = 'منطقه';
                }
                if ($customer->budget_min || $customer->budget_max) {
                    $price = $property->price ?? $property->rent ?? $property->deposit;
                    if ($price) {
                        $min = $customer->budget_min ?? 0;
                        $max = $customer->budget_max ?? PHP_INT_MAX;
                        if ($price >= $min && $price <= $max) {
                            $score += 25;
                            $reasons[] = 'بودجه';
                        }
                    }
                }
                if ($customer->min_area && $property->area && $property->area >= $customer->min_area) {
                    $score += 10;
                    $reasons[] = 'متراژ';
                }
                if ($customer->max_area && $property->area && $property->area <= $customer->max_area) {
                    $score += 5;
                }
                if ($customer->min_rooms && $property->rooms && $property->rooms >= $customer->min_rooms) {
                    $score += 10;
                    $reasons[] = 'تعداد خواب';
                }

                return [
                    'property' => $property,
                    'score' => $score,
                    'reasons' => $reasons,
                ];
            })
            ->filter(fn ($item) => $item['score'] > 0)
            ->sortByDesc('score')
            ->take($limit)
            ->values();
    }
}
