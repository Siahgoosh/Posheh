<?php

namespace App\Services\Crm;

use App\Models\CrmMatchingWeight;
use App\Models\CrmNeedProfile;
use App\Models\CrmPropertyFeedback;
use App\Models\Customer;
use App\Models\Property;
use App\Models\User;
use Illuminate\Support\Collection;

class PropertyMatchingService
{
    public function __construct(
        private readonly CrmBootstrapService $bootstrap,
    ) {}

    /** @return list<array{key:string,label:string,weight:int}> */
    public function defaultWeights(): array
    {
        return [
            ['key' => 'location', 'label' => 'موقعیت', 'weight' => 25],
            ['key' => 'budget', 'label' => 'بودجه', 'weight' => 25],
            ['key' => 'area', 'label' => 'متراژ', 'weight' => 15],
            ['key' => 'property_type', 'label' => 'نوع ملک/معامله', 'weight' => 10],
            ['key' => 'bedrooms', 'label' => 'خواب', 'weight' => 5],
            ['key' => 'parking', 'label' => 'پارکینگ', 'weight' => 5],
            ['key' => 'elevator', 'label' => 'آسانسور', 'weight' => 5],
            ['key' => 'age', 'label' => 'سن بنا', 'weight' => 5],
            ['key' => 'features', 'label' => 'امکانات', 'weight' => 5],
        ];
    }

    public function ensureWeights(int $officeId): Collection
    {
        foreach ($this->defaultWeights() as $row) {
            CrmMatchingWeight::query()->firstOrCreate(
                ['office_id' => $officeId, 'key' => $row['key']],
                ['label' => $row['label'], 'weight' => $row['weight'], 'is_active' => true]
            );
        }

        return CrmMatchingWeight::where('office_id', $officeId)->where('is_active', true)->get();
    }

    public function weightsMap(int $officeId): array
    {
        return $this->ensureWeights($officeId)->pluck('weight', 'key')->all();
    }

    public function resolveNeed(Customer $customer): array
    {
        $profile = CrmNeedProfile::where('customer_id', $customer->id)->first();

        return [
            'transaction_type' => $profile?->transaction_type ?? $customer->preferred_type,
            'property_type' => $profile?->property_type,
            'budget_min' => $profile?->budget_min ?? $customer->budget_min,
            'budget_max' => $profile?->budget_max ?? $customer->budget_max,
            'min_area' => $profile?->min_area ?? $customer->min_area,
            'max_area' => $profile?->max_area ?? $customer->max_area,
            'bedrooms' => $profile?->bedrooms ?? $customer->min_rooms,
            'preferred_city' => $customer->preferred_city ?? $customer->city,
            'preferred_district' => $customer->preferred_district,
            'preferred_locations' => $profile?->preferred_locations ?? array_filter([
                $customer->preferred_city,
                $customer->preferred_district,
            ]),
            'excluded_locations' => $profile?->excluded_locations ?? [],
            'parking_required' => $profile?->parking_required,
            'elevator_preferred' => $profile?->elevator_preferred,
            'storage_preferred' => $profile?->storage_preferred,
            'max_building_age' => $profile?->max_building_age,
            'preferred_features' => $profile?->preferred_features ?? [],
            'priority_weights' => $profile?->priority_weights ?? [],
        ];
    }

    /**
     * @return array{score:int,band:string,checks:list<array{ok:bool,label:string,detail?:string}>,budget_diff_percent:?float,match_type:string}
     */
    public function scoreProperty(Property $property, array $need, array $weights, ?Customer $customer = null): array
    {
        $checks = [];
        $earned = 0;
        $possible = 0;

        $add = function (string $key, bool $ok, string $label, ?string $detail = null) use (&$earned, &$possible, &$checks, $weights) {
            $w = (int) ($weights[$key] ?? 0);
            if ($w <= 0) {
                return;
            }
            $possible += $w;
            if ($ok) {
                $earned += $w;
            }
            $checks[] = ['ok' => $ok, 'label' => $label, 'detail' => $detail, 'key' => $key, 'weight' => $w];
        };

        // Location
        $locOk = false;
        $locDetail = null;
        $locs = $need['preferred_locations'] ?? [];
        $city = (string) ($need['preferred_city'] ?? '');
        $district = (string) ($need['preferred_district'] ?? '');
        if ($city && $property->city && str_contains((string) $property->city, $city)) {
            $locOk = true;
            $locDetail = 'شهر مطابق';
        }
        if ($district && $property->district && str_contains((string) $property->district, $district)) {
            $locOk = true;
            $locDetail = trim(($locDetail ? $locDetail.' · ' : '').'منطقه مطابق');
        }
        foreach ($locs as $loc) {
            if (! $loc) {
                continue;
            }
            if (($property->city && str_contains((string) $property->city, (string) $loc))
                || ($property->district && str_contains((string) $property->district, (string) $loc))
                || ($property->neighborhood && str_contains((string) $property->neighborhood, (string) $loc))) {
                $locOk = true;
                $locDetail = 'محدوده مطابق';
                break;
            }
        }
        if ($city || $district || $locs) {
            $add('location', $locOk, 'موقعیت', $locDetail);
        }

        // Budget
        $price = (int) ($property->price ?? $property->rent ?? $property->deposit ?? 0);
        $min = (int) ($need['budget_min'] ?? 0);
        $max = (int) ($need['budget_max'] ?? 0);
        $budgetDiff = null;
        $budgetOk = false;
        if ($price > 0 && ($min > 0 || $max > 0)) {
            $ceiling = $max > 0 ? $max : PHP_INT_MAX;
            $floor = $min > 0 ? $min : 0;
            if ($price >= $floor && $price <= $ceiling) {
                $budgetOk = true;
            } elseif ($max > 0 && $price > $max) {
                $budgetDiff = round((($price - $max) / max($max, 1)) * 100, 1);
                // soft match within 10%
                $budgetOk = $budgetDiff <= 10;
            }
            $add('budget', $budgetOk && ($budgetDiff === null || $budgetDiff === 0.0), 'بودجه',
                $budgetDiff ? "اختلاف بودجه {$budgetDiff}٪" : ($budgetOk ? 'در محدوده بودجه' : 'خارج از بودجه'));
            if ($budgetDiff && $budgetDiff <= 10) {
                // give partial credit already via ok=true soft — mark as warning in detail
            }
        }

        // Area
        if (($need['min_area'] || $need['max_area']) && $property->area) {
            $areaOk = true;
            if ($need['min_area'] && $property->area < $need['min_area']) {
                $areaOk = false;
            }
            if ($need['max_area'] && $property->area > $need['max_area']) {
                $areaOk = false;
            }
            $add('area', $areaOk, 'متراژ', $property->area.' متر');
        }

        // Type
        if ($need['transaction_type'] || $need['property_type']) {
            $typeVal = $property->type?->value ?? (string) $property->type;
            $ok = false;
            if ($need['transaction_type'] && $typeVal && str_contains($typeVal, (string) $need['transaction_type'])) {
                $ok = true;
            }
            if ($need['property_type'] && ($property->property_category ?? null) === $need['property_type']) {
                $ok = true;
            }
            if ($need['transaction_type'] && $typeVal === $need['transaction_type']) {
                $ok = true;
            }
            // preferred_type on customer often is sale/rent
            if ($need['transaction_type'] && $typeVal === $need['transaction_type']) {
                $ok = true;
            }
            $add('property_type', $ok || (! $need['transaction_type'] && ! $need['property_type']), 'نوع معامله/ملک', $typeVal);
        }

        // Bedrooms
        if ($need['bedrooms'] && $property->rooms !== null) {
            $add('bedrooms', (int) $property->rooms >= (int) $need['bedrooms'], 'تعداد خواب', (string) $property->rooms);
        }

        // Parking
        if ($need['parking_required'] === true) {
            $add('parking', (bool) $property->has_parking, 'پارکینگ', $property->has_parking ? 'دارد' : 'ندارد');
        }

        // Elevator
        if ($need['elevator_preferred'] === true) {
            $add('elevator', (bool) $property->has_elevator, 'آسانسور', $property->has_elevator ? 'دارد' : 'ندارد');
        }

        // Age
        if ($need['max_building_age'] && $property->building_age !== null) {
            $add('age', (int) $property->building_age <= (int) $need['max_building_age'], 'سن بنا', (string) $property->building_age);
        }

        // Features from feedback learning
        if ($customer) {
            $dislikes = CrmPropertyFeedback::where('customer_id', $customer->id)
                ->whereIn('reaction', ['no_parking', 'no_elevator', 'price_high'])
                ->pluck('reaction')
                ->unique();
            if ($dislikes->contains('no_parking') && ! $property->has_parking) {
                $checks[] = ['ok' => false, 'label' => 'یادگیری بازخورد: پارکینگ ضروری', 'detail' => 'مشتری قبلاً عدم پارکینگ را رد کرده', 'key' => 'features', 'weight' => 0];
                $earned = max(0, $earned - 5);
            }
        }

        $score = $possible > 0 ? (int) round(($earned / $possible) * 100) : 0;
        $score = max(0, min(100, $score));

        $matchType = 'none';
        if ($score >= 85 && ($budgetDiff === null || $budgetDiff == 0.0)) {
            $matchType = 'exact';
        } elseif ($score >= 70) {
            $matchType = 'strong';
        } elseif ($score >= 45 || ($budgetDiff !== null && $budgetDiff <= 10)) {
            $matchType = 'alternative';
        }

        $band = match (true) {
            $score >= 81 => 'very_hot',
            $score >= 61 => 'hot',
            $score >= 41 => 'warm',
            $score >= 21 => 'low',
            default => 'cold',
        };

        return [
            'score' => $score,
            'band' => $band,
            'checks' => $checks,
            'budget_diff_percent' => $budgetDiff,
            'match_type' => $matchType,
        ];
    }

    public function matchForCustomer(User $user, Customer $customer, int $limit = 20): Collection
    {
        $weights = $this->weightsMap((int) $user->office_id);
        $need = $this->resolveNeed($customer);

        return Property::where('office_id', $user->office_id)
            ->where('status', 'active')
            ->with('media')
            ->limit(500)
            ->get()
            ->map(function (Property $property) use ($need, $weights, $customer) {
                $result = $this->scoreProperty($property, $need, $weights, $customer);

                return [
                    'property' => $property,
                    'score' => $result['score'],
                    'band' => $result['band'],
                    'match_type' => $result['match_type'],
                    'budget_diff_percent' => $result['budget_diff_percent'],
                    'checks' => $result['checks'],
                    'reasons' => collect($result['checks'])->where('ok', true)->pluck('label')->values()->all(),
                    'warnings' => collect($result['checks'])->where('ok', false)->map(fn ($c) => $c['detail'] ?? $c['label'])->values()->all(),
                ];
            })
            ->filter(fn ($row) => $row['score'] >= 40)
            ->sortByDesc('score')
            ->take($limit)
            ->values();
    }

    public function reverseMatchForProperty(User $user, Property $property, int $limit = 30): array
    {
        $weights = $this->weightsMap((int) $user->office_id);
        $customers = Customer::where('office_id', $user->office_id)
            ->where(function ($q) {
                $q->whereNull('lifecycle')->orWhereNotIn('lifecycle', ['closed']);
            })
            ->limit(300)
            ->get();

        $rows = $customers->map(function (Customer $customer) use ($property, $weights) {
            $need = $this->resolveNeed($customer);
            $result = $this->scoreProperty($property, $need, $weights, $customer);

            return [
                'customer' => $customer->only(['id', 'name', 'mobile', 'lead_score', 'priority', 'lifecycle']),
                'score' => $result['score'],
                'band' => $result['band'],
                'match_type' => $result['match_type'],
                'checks' => $result['checks'],
            ];
        })
            ->filter(fn ($r) => $r['score'] >= 45)
            ->sortByDesc('score')
            ->take($limit)
            ->values();

        $byBand = [
            'hot' => $rows->filter(fn ($r) => in_array($r['band'], ['hot', 'very_hot'], true))->count(),
            'warm' => $rows->where('band', 'warm')->count(),
            'cold' => $rows->filter(fn ($r) => in_array($r['band'], ['cold', 'low'], true))->count(),
        ];

        return [
            'total' => $rows->count(),
            'summary' => $byBand,
            'customers' => $rows,
        ];
    }
}
