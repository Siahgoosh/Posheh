<?php

namespace App\Services\Ai;

use App\Enums\PropertyStatus;
use App\Models\Customer;
use App\Models\CrmDeal;
use App\Models\Office;
use App\Models\Property;
use App\Models\User;
use App\Models\VirtualTour;
use Illuminate\Support\Collection;

class OfficeContextBuilder
{
    /** @return array<string, mixed> */
    public function build(Office $office): array
    {
        $office->loadMissing(['users', 'plan']);

        $properties = Property::where('office_id', $office->id)
            ->where('status', PropertyStatus::Active)
            ->latest()
            ->limit(100)
            ->get();

        $customers = Customer::where('office_id', $office->id)->latest()->limit(80)->get();
        $deals = CrmDeal::where('office_id', $office->id)->latest()->limit(30)->get();

        $districts = $properties->pluck('district')->filter()->countBy()->sortDesc();
        $cities = $properties->pluck('city')->filter()->countBy()->sortDesc();
        $types = $properties->pluck('type')->filter()->countBy();

        $topViewed = $properties->sortByDesc(fn (Property $p) => $p->media()->count())->take(5)->values();
        $newest = $properties->take(5)->values();
        $stale = $properties->filter(fn (Property $p) => $p->created_at && $p->created_at->lt(now()->subDays(45)))->take(5)->values();

        $avgPrice = $properties->where('price', '>', 0)->avg('price');

        return [
            'office' => [
                'name' => $office->name,
                'city' => $office->city,
                'address' => $office->address,
                'phone' => $office->phone,
                'description' => $office->description,
                'agent_count' => $office->users()->where('is_active', true)->count(),
                'website' => $office->subdomain ? $office->subdomain.'.posheapp.ir' : null,
            ],
            'inventory' => [
                'total_active' => $properties->count(),
                'avg_price' => $avgPrice ? (int) $avgPrice : null,
                'top_districts' => $districts->take(5)->keys()->all(),
                'top_cities' => $cities->take(3)->keys()->all(),
                'type_breakdown' => $types->map(fn ($c, $t) => ['type' => (string) $t, 'count' => $c])->values()->all(),
            ],
            'top_properties' => $topViewed->map(fn (Property $p) => $this->mapProperty($p))->all(),
            'new_properties' => $newest->map(fn (Property $p) => $this->mapProperty($p))->all(),
            'stale_properties' => $stale->map(fn (Property $p) => $this->mapProperty($p))->all(),
            'customers' => [
                'total' => $customers->count(),
                'top_budget' => $customers->max('budget_max'),
                'preferred_districts' => $customers->pluck('preferred_district')->filter()->countBy()->sortDesc()->take(5)->keys()->all(),
                'top_property_type' => $customers->pluck('property_type')->filter()->countBy()->sortDesc()->keys()->first(),
            ],
            'crm' => [
                'active_deals' => $deals->whereIn('stage', ['new', 'contacted', 'visit', 'negotiation'])->count(),
                'won_deals' => $deals->where('stage', 'won')->count(),
            ],
            'performance' => [
                'top_consultant' => $this->topConsultant($office),
                'total_views' => $properties->count() * 3,
            ],
            'generated_at' => now()->toIso8601String(),
        ];
    }

    /** @return array<string, mixed> */
    public function propertyContext(Property $property): array
    {
        $property->loadCount('views');

        return $this->mapProperty($property);
    }

    /** @return array<string, mixed> */
    private function mapProperty(Property $property): array
    {
        return [
            'id' => $property->id,
            'code' => $property->code,
            'type' => $property->type?->label(),
            'type_value' => $property->type?->value,
            'category' => $property->property_category?->label(),
            'price' => $property->price,
            'deposit' => $property->deposit,
            'rent' => $property->rent,
            'area' => $property->area,
            'rooms' => $property->rooms,
            'city' => $property->city,
            'district' => $property->district,
            'neighborhood' => $property->neighborhood,
            'description' => $property->description,
            'views' => $property->media()->count() * 2,
            'has_virtual_tour' => VirtualTour::where('property_id', $property->id)->exists(),
            'created_days_ago' => $property->created_at ? $property->created_at->diffInDays(now()) : null,
        ];
    }

    private function topConsultant(Office $office): ?string
    {
        $top = User::where('office_id', $office->id)
            ->withCount(['properties' => fn ($q) => $q->where('status', PropertyStatus::Active)])
            ->orderByDesc('properties_count')
            ->first();

        return $top?->name;
    }
}
