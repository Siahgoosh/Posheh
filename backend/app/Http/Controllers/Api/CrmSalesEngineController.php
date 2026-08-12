<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CrmAutomationLog;
use App\Models\CrmAutomationRule;
use App\Models\CrmCampaign;
use App\Models\CrmDeal;
use App\Models\CrmDealChecklistItem;
use App\Models\CrmMatchingWeight;
use App\Models\CrmNegotiation;
use App\Models\CrmPropertyFeedback;
use App\Models\CrmPropertyPresentation;
use App\Models\Customer;
use App\Models\Property;
use App\Services\Crm\CrmAutomationService;
use App\Services\Crm\OfferNegotiationService;
use App\Services\Crm\PropertyMatchingService;
use App\Services\Crm\SalesQueueService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CrmSalesEngineController extends Controller
{
    public function __construct(
        private readonly SalesQueueService $queue,
        private readonly PropertyMatchingService $matching,
        private readonly OfferNegotiationService $offers,
        private readonly CrmAutomationService $automation,
    ) {}

    public function salesQueue(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->queue->todayQueue($request->user())]);
    }

    public function opportunities(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->queue->opportunities($request->user())]);
    }

    public function briefing(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->queue->dailyBriefing($request->user())]);
    }

    public function matchingWeights(Request $request): JsonResponse
    {
        $weights = $this->matching->ensureWeights((int) $request->user()->office_id);

        return response()->json(['data' => $weights]);
    }

    public function updateMatchingWeights(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user->canManageOffice()) {
            abort(403);
        }
        $data = $request->validate([
            'weights' => ['required', 'array'],
            'weights.*.key' => ['required', 'string'],
            'weights.*.weight' => ['required', 'integer', 'min:0', 'max:100'],
            'weights.*.is_active' => ['nullable', 'boolean'],
        ]);

        $this->matching->ensureWeights((int) $user->office_id);
        foreach ($data['weights'] as $row) {
            CrmMatchingWeight::where('office_id', $user->office_id)
                ->where('key', $row['key'])
                ->update([
                    'weight' => $row['weight'],
                    'is_active' => $row['is_active'] ?? true,
                ]);
        }

        return response()->json(['data' => $this->matching->ensureWeights((int) $user->office_id)]);
    }

    public function reverseMatch(Request $request, int $propertyId): JsonResponse
    {
        $property = Property::where('office_id', $request->user()->office_id)->findOrFail($propertyId);

        return response()->json([
            'data' => $this->matching->reverseMatchForProperty($request->user(), $property),
        ]);
    }

    public function listNegotiations(Request $request): JsonResponse
    {
        return response()->json($this->offers->listNegotiations($request->user()));
    }

    public function startNegotiation(Request $request): JsonResponse
    {
        $data = $request->validate([
            'property_id' => ['required', 'integer'],
            'customer_id' => ['nullable', 'integer'],
            'crm_deal_id' => ['nullable', 'integer'],
            'agent_id' => ['nullable', 'integer'],
            'buyer_name' => ['nullable', 'string', 'max:120'],
            'seller_name' => ['nullable', 'string', 'max:120'],
            'initial_price' => ['nullable', 'integer', 'min:0'],
            'target_price' => ['nullable', 'integer', 'min:0'],
            'minimum_acceptable' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        return response()->json([
            'data' => $this->offers->startNegotiation($request->user(), $data),
        ], 201);
    }

    public function showNegotiation(Request $request, int $id): JsonResponse
    {
        $n = CrmNegotiation::with(['property', 'customer', 'offers', 'deal'])
            ->where('office_id', $request->user()->office_id)
            ->findOrFail($id);

        return response()->json(['data' => $n]);
    }

    public function listOffers(Request $request): JsonResponse
    {
        return response()->json(
            $this->offers->listOffers($request->user(), $request->query('status'))
        );
    }

    public function storeOffer(Request $request): JsonResponse
    {
        $data = $request->validate([
            'property_id' => ['required', 'integer'],
            'negotiation_id' => ['nullable', 'integer'],
            'crm_deal_id' => ['nullable', 'integer'],
            'customer_id' => ['nullable', 'integer'],
            'side' => ['nullable', 'string', 'in:buyer,seller'],
            'amount' => ['required', 'integer', 'min:0'],
            'payment_terms' => ['nullable', 'string'],
            'deposit' => ['nullable', 'integer', 'min:0'],
            'installments' => ['nullable', 'string'],
            'deadline_at' => ['nullable', 'date'],
            'status' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        return response()->json([
            'data' => $this->offers->addOffer($request->user(), $data),
        ], 201);
    }

    public function updateOfferStatus(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', 'string'],
        ]);

        return response()->json([
            'data' => $this->offers->updateOfferStatus($request->user(), $id, $data['status']),
        ]);
    }

    public function presentProperty(Request $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'customer_id' => ['required', 'integer'],
            'property_id' => ['required', 'integer'],
            'crm_deal_id' => ['nullable', 'integer'],
            'channel' => ['nullable', 'string', 'in:whatsapp,telegram,sms,email,manual'],
            'match_score' => ['nullable', 'integer', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string'],
        ]);
        Customer::where('office_id', $user->office_id)->findOrFail($data['customer_id']);
        Property::where('office_id', $user->office_id)->findOrFail($data['property_id']);

        $row = CrmPropertyPresentation::create([
            'office_id' => $user->office_id,
            'customer_id' => $data['customer_id'],
            'property_id' => $data['property_id'],
            'crm_deal_id' => $data['crm_deal_id'] ?? null,
            'agent_id' => $user->id,
            'channel' => $data['channel'] ?? 'manual',
            'match_score' => $data['match_score'] ?? null,
            'sent_at' => now(),
            'notes' => $data['notes'] ?? null,
        ]);

        if (! empty($data['crm_deal_id'])) {
            $deal = CrmDeal::where('office_id', $user->office_id)->find($data['crm_deal_id']);
            if ($deal) {
                \App\Models\CrmActivity::create([
                    'crm_deal_id' => $deal->id,
                    'user_id' => $user->id,
                    'type' => 'note',
                    'body' => 'ارسال فایل ملک #'.$data['property_id'].' از طریق '.($data['channel'] ?? 'manual'),
                    'meta' => ['presentation_id' => $row->id],
                ]);
            }
        }

        return response()->json(['data' => $row], 201);
    }

    public function storeFeedback(Request $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'customer_id' => ['required', 'integer'],
            'property_id' => ['required', 'integer'],
            'crm_deal_id' => ['nullable', 'integer'],
            'property_visit_id' => ['nullable', 'integer'],
            'reaction' => ['required', 'string', 'max:40'],
            'rating' => ['nullable', 'integer', 'min:1', 'max:5'],
            'likelihood' => ['nullable', 'string', 'max:40'],
            'comment' => ['nullable', 'string'],
        ]);
        Customer::where('office_id', $user->office_id)->findOrFail($data['customer_id']);
        Property::where('office_id', $user->office_id)->findOrFail($data['property_id']);

        $feedback = CrmPropertyFeedback::create([
            ...$data,
            'office_id' => $user->office_id,
            'created_by' => $user->id,
        ]);

        // Learn: parking required from "no_parking" dislike
        if (in_array($data['reaction'], ['no_parking', 'no_elevator', 'price_high'], true)) {
            $profile = \App\Models\CrmNeedProfile::firstOrCreate(
                ['customer_id' => $data['customer_id']],
                ['office_id' => $user->office_id]
            );
            if ($data['reaction'] === 'no_parking') {
                $profile->update(['parking_required' => true]);
            }
            if ($data['reaction'] === 'no_elevator') {
                $profile->update(['elevator_preferred' => true]);
            }
        }

        return response()->json(['data' => $feedback], 201);
    }

    public function automationRules(Request $request): JsonResponse
    {
        $this->automation->ensureDefaultRules((int) $request->user()->office_id);
        $items = CrmAutomationRule::where('office_id', $request->user()->office_id)
            ->orderBy('id')
            ->get();

        return response()->json(['data' => $items]);
    }

    public function storeAutomationRule(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user->canManageOffice()) {
            abort(403);
        }
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'trigger' => ['required', 'string', 'max:60'],
            'conditions' => ['nullable', 'array'],
            'actions' => ['required', 'array'],
            'delay_minutes' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $rule = CrmAutomationRule::create([
            'office_id' => $user->office_id,
            'name' => $data['name'],
            'trigger' => $data['trigger'],
            'conditions' => $data['conditions'] ?? [],
            'actions' => $data['actions'],
            'delay_minutes' => $data['delay_minutes'] ?? 0,
            'is_active' => $data['is_active'] ?? true,
            'is_system' => false,
        ]);

        return response()->json(['data' => $rule], 201);
    }

    public function updateAutomationRule(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        if (! $user->canManageOffice()) {
            abort(403);
        }
        $rule = CrmAutomationRule::where('office_id', $user->office_id)->findOrFail($id);
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:120'],
            'conditions' => ['nullable', 'array'],
            'actions' => ['sometimes', 'array'],
            'delay_minutes' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $rule->update($data);

        return response()->json(['data' => $rule->fresh()]);
    }

    public function automationLogs(Request $request): JsonResponse
    {
        $items = CrmAutomationLog::where('office_id', $request->user()->office_id)
            ->orderByDesc('id')
            ->limit(100)
            ->get();

        return response()->json(['data' => $items]);
    }

    public function dealChecklist(Request $request, int $dealId): JsonResponse
    {
        $deal = CrmDeal::where('office_id', $request->user()->office_id)->findOrFail($dealId);
        $this->automation->ensureDealChecklist($deal);
        $items = CrmDealChecklistItem::where('crm_deal_id', $deal->id)->orderBy('sort_order')->get();

        return response()->json(['data' => $items]);
    }

    public function toggleChecklistItem(Request $request, int $dealId, int $itemId): JsonResponse
    {
        $deal = CrmDeal::where('office_id', $request->user()->office_id)->findOrFail($dealId);
        $item = CrmDealChecklistItem::where('crm_deal_id', $deal->id)->findOrFail($itemId);
        $done = ! $item->is_done;
        $item->update([
            'is_done' => $done,
            'done_at' => $done ? now() : null,
            'done_by' => $done ? $request->user()->id : null,
        ]);

        return response()->json(['data' => $item->fresh()]);
    }

    public function campaigns(Request $request): JsonResponse
    {
        $items = CrmCampaign::where('office_id', $request->user()->office_id)
            ->orderByDesc('id')
            ->get();

        return response()->json(['data' => $items]);
    }

    public function storeCampaign(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user->canManageOffice()) {
            abort(403);
        }
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'source' => ['nullable', 'string', 'max:50'],
            'budget' => ['nullable', 'integer', 'min:0'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $campaign = CrmCampaign::create([
            ...$data,
            'office_id' => $user->office_id,
            'is_active' => $data['is_active'] ?? true,
        ]);

        return response()->json(['data' => $campaign], 201);
    }

    public function convertOfferToDeal(Request $request, int $offerId): JsonResponse
    {
        $user = $request->user();
        $offer = \App\Models\CrmOffer::where('office_id', $user->office_id)->findOrFail($offerId);
        if ($offer->status !== 'accepted' && $request->boolean('force') !== true) {
            return response()->json(['message' => 'فقط پیشنهاد پذیرفته‌شده قابل تبدیل است.'], 422);
        }

        $deal = null;
        if ($offer->crm_deal_id) {
            $deal = CrmDeal::where('office_id', $user->office_id)->find($offer->crm_deal_id);
        }
        if (! $deal) {
            $deal = CrmDeal::create([
                'office_id' => $user->office_id,
                'created_by' => $user->id,
                'assigned_to' => $user->id,
                'property_id' => $offer->property_id,
                'customer_id' => $offer->customer_id,
                'title' => 'معامله از پیشنهاد #'.$offer->id,
                'stage' => 'closed_won',
                'value' => $offer->amount,
                'offer_amount' => $offer->amount,
                'deal_status' => 'agreement',
                'priority' => 'high',
                'lead_score' => 90,
            ]);
            $offer->update(['crm_deal_id' => $deal->id]);
        } else {
            $deal->update([
                'stage' => 'closed_won',
                'value' => $offer->amount,
                'offer_amount' => $offer->amount,
                'deal_status' => 'agreement',
            ]);
        }

        $this->automation->ensureDealChecklist($deal);

        return response()->json(['data' => $deal->fresh(['property', 'customer'])]);
    }
}
