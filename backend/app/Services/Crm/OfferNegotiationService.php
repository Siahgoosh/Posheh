<?php

namespace App\Services\Crm;

use App\Models\CrmDeal;
use App\Models\CrmNegotiation;
use App\Models\CrmOffer;
use App\Models\Customer;
use App\Models\Property;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OfferNegotiationService
{
    public function __construct(
        private readonly CrmAutomationService $automation,
    ) {}

    public function startNegotiation(User $user, array $data): CrmNegotiation
    {
        $property = Property::where('office_id', $user->office_id)->findOrFail($data['property_id']);
        if (! empty($data['customer_id'])) {
            Customer::where('office_id', $user->office_id)->findOrFail($data['customer_id']);
        }
        if (! empty($data['crm_deal_id'])) {
            CrmDeal::where('office_id', $user->office_id)->findOrFail($data['crm_deal_id']);
        }

        $negotiation = CrmNegotiation::create([
            'office_id' => $user->office_id,
            'property_id' => $property->id,
            'customer_id' => $data['customer_id'] ?? null,
            'crm_deal_id' => $data['crm_deal_id'] ?? null,
            'agent_id' => $data['agent_id'] ?? $user->id,
            'buyer_name' => $data['buyer_name'] ?? null,
            'seller_name' => $data['seller_name'] ?? null,
            'initial_price' => $data['initial_price'] ?? $property->price,
            'current_price' => $data['initial_price'] ?? $property->price,
            'target_price' => $data['target_price'] ?? null,
            'minimum_acceptable' => $data['minimum_acceptable'] ?? $property->minimum_acceptable_price,
            'status' => 'open',
            'notes' => $data['notes'] ?? null,
        ]);

        if (! empty($data['crm_deal_id'])) {
            CrmDeal::where('id', $data['crm_deal_id'])->update(['stage' => 'negotiation']);
        }

        return $negotiation->load(['property', 'customer', 'offers']);
    }

    public function addOffer(User $user, array $data): CrmOffer
    {
        $property = Property::where('office_id', $user->office_id)->findOrFail($data['property_id']);
        $negotiation = null;
        if (! empty($data['negotiation_id'])) {
            $negotiation = CrmNegotiation::where('office_id', $user->office_id)->findOrFail($data['negotiation_id']);
        }

        return DB::transaction(function () use ($user, $data, $property, $negotiation) {
            $offer = CrmOffer::create([
                'office_id' => $user->office_id,
                'negotiation_id' => $negotiation?->id,
                'crm_deal_id' => $data['crm_deal_id'] ?? $negotiation?->crm_deal_id,
                'property_id' => $property->id,
                'customer_id' => $data['customer_id'] ?? $negotiation?->customer_id,
                'created_by' => $user->id,
                'side' => $data['side'] ?? 'buyer',
                'amount' => (int) $data['amount'],
                'payment_terms' => $data['payment_terms'] ?? null,
                'deposit' => $data['deposit'] ?? null,
                'installments' => $data['installments'] ?? null,
                'deadline_at' => $data['deadline_at'] ?? null,
                'status' => $data['status'] ?? 'submitted',
                'notes' => $data['notes'] ?? null,
            ]);

            if ($negotiation) {
                $negotiation->update(['current_price' => $offer->amount]);
            }

            if (! empty($offer->crm_deal_id)) {
                CrmDeal::where('id', $offer->crm_deal_id)->update(['offer_amount' => $offer->amount]);
                $deal = CrmDeal::find($offer->crm_deal_id);
                if ($deal) {
                    $this->automation->dispatch($user, 'offer_created', $deal, ['offer_id' => $offer->id]);
                }
            }

            return $offer->load(['property', 'customer', 'negotiation']);
        });
    }

    public function updateOfferStatus(User $user, int $id, string $status): CrmOffer
    {
        $offer = CrmOffer::where('office_id', $user->office_id)->findOrFail($id);
        $allowed = ['draft', 'submitted', 'accepted', 'rejected', 'countered', 'expired', 'withdrawn'];
        if (! in_array($status, $allowed, true)) {
            throw ValidationException::withMessages(['status' => ['وضعیت نامعتبر است.']]);
        }
        $offer->update(['status' => $status]);

        if ($status === 'accepted' && $offer->crm_deal_id) {
            CrmDeal::where('id', $offer->crm_deal_id)->update([
                'offer_amount' => $offer->amount,
                'deal_status' => 'agreement',
            ]);
            if ($offer->negotiation_id) {
                CrmNegotiation::where('id', $offer->negotiation_id)->update([
                    'status' => 'agreed',
                    'current_price' => $offer->amount,
                ]);
            }
        }

        return $offer->fresh();
    }

    public function listNegotiations(User $user)
    {
        return CrmNegotiation::with(['property:id,code,city', 'customer:id,name', 'offers'])
            ->where('office_id', $user->office_id)
            ->when(! $user->canManageOffice(), fn ($q) => $q->where('agent_id', $user->id))
            ->orderByDesc('updated_at')
            ->paginate(30);
    }

    public function listOffers(User $user, ?string $status = null)
    {
        return CrmOffer::with(['property:id,code', 'customer:id,name'])
            ->where('office_id', $user->office_id)
            ->when($status, fn ($q) => $q->where('status', $status))
            ->orderByDesc('id')
            ->paginate(30);
    }
}
