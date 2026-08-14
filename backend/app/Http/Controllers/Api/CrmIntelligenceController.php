<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CrmCustomField;
use App\Models\CrmCustomFieldValue;
use App\Models\CrmIntegration;
use App\Models\CrmMessageTemplate;
use App\Models\CrmNotificationPreference;
use App\Models\CrmOnboardingItem;
use App\Models\CrmPipelineProbability;
use App\Models\CrmSavedView;
use App\Models\Customer;
use App\Models\Property;
use App\Services\Ai\AiService;
use App\Services\Crm\CrmCommunicationService;
use App\Services\Crm\CrmIntelligenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CrmIntelligenceController extends Controller
{
    public function __construct(
        private readonly CrmIntelligenceService $intel,
        private readonly AiService $ai,
        private readonly CrmCommunicationService $comm,
    ) {}

    public function executive(Request $request): JsonResponse
    {
        $period = $request->query('period', 'this_month');

        return response()->json([
            'data' => $this->intel->executiveDashboard($request->user(), $period),
        ]);
    }

    public function agentDashboard(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->intel->agentDashboard($request->user())]);
    }

    public function funnel(Request $request): JsonResponse
    {
        $funnel = $this->intel->funnelAnalytics($request->user());

        return response()->json([
            'data' => [
                'funnel' => $funnel,
                'bottlenecks' => $this->intel->detectBottlenecks($funnel),
            ],
        ]);
    }

    public function forecast(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->intel->forecast($request->user())]);
    }

    public function agents(Request $request): JsonResponse
    {
        if (! $request->user()->canManageOffice()) {
            abort(403);
        }

        return response()->json(['data' => $this->intel->agentLeaderboard($request->user())]);
    }

    public function sources(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->intel->sourceIntelligence($request->user())]);
    }

    public function propertyIntel(Request $request, int $propertyId): JsonResponse
    {
        $property = Property::where('office_id', $request->user()->office_id)->findOrFail($propertyId);

        return response()->json(['data' => $this->intel->propertyDemandScore($property)]);
    }

    public function dataQuality(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->intel->dataQuality($request->user())]);
    }

    public function probabilities(Request $request): JsonResponse
    {
        $this->intel->ensureProbabilities((int) $request->user()->office_id);

        return response()->json([
            'data' => CrmPipelineProbability::where('office_id', $request->user()->office_id)->get(),
        ]);
    }

    public function updateProbabilities(Request $request): JsonResponse
    {
        if (! $request->user()->canManageOffice()) {
            abort(403);
        }
        $data = $request->validate([
            'items' => ['required', 'array'],
            'items.*.stage_key' => ['required', 'string'],
            'items.*.probability' => ['required', 'integer', 'min:0', 'max:100'],
        ]);
        foreach ($data['items'] as $row) {
            CrmPipelineProbability::updateOrCreate(
                ['office_id' => $request->user()->office_id, 'stage_key' => $row['stage_key']],
                ['probability' => $row['probability']]
            );
        }

        return $this->probabilities($request);
    }

    // --- AI ---
    public function aiCustomerSummary(Request $request, int $customerId): JsonResponse
    {
        $customer = Customer::where('office_id', $request->user()->office_id)->findOrFail($customerId);

        return response()->json(['data' => $this->ai->customerSummary($request->user(), $customer)]);
    }

    public function aiMessage(Request $request): JsonResponse
    {
        $data = $request->validate([
            'purpose' => ['nullable', 'string', 'max:40'],
            'customer_name' => ['nullable', 'string', 'max:120'],
            'property_title' => ['nullable', 'string', 'max:255'],
            'price' => ['nullable', 'integer', 'min:0'],
        ]);

        return response()->json(['data' => $this->ai->generateMessage($request->user(), $data)]);
    }

    public function aiListing(Request $request, int $propertyId): JsonResponse
    {
        $property = Property::where('office_id', $request->user()->office_id)->findOrFail($propertyId);

        return response()->json(['data' => $this->ai->listingAssistant($request->user(), $property)]);
    }

    public function aiUsage(Request $request): JsonResponse
    {
        $this->ai->ensureDefaultPrompts((int) $request->user()->office_id);

        return response()->json(['data' => $this->ai->usageForOffice($request->user())]);
    }

    // --- Notifications ---
    public function notifications(Request $request): JsonResponse
    {
        $this->comm->ensureDefaultPreferences($request->user());

        return response()->json([
            'data' => $this->comm->listNotifications($request->user(), $request->boolean('unread')),
        ]);
    }

    public function markNotificationRead(Request $request, int $id): JsonResponse
    {
        return response()->json(['data' => $this->comm->markRead($request->user(), $id)]);
    }

    public function notificationPreferences(Request $request): JsonResponse
    {
        $this->comm->ensureDefaultPreferences($request->user());

        return response()->json([
            'data' => CrmNotificationPreference::where('user_id', $request->user()->id)->get(),
        ]);
    }

    public function updateNotificationPreferences(Request $request): JsonResponse
    {
        $data = $request->validate([
            'items' => ['required', 'array'],
            'items.*.category' => ['required', 'string'],
            'items.*.in_app' => ['nullable', 'boolean'],
            'items.*.sms' => ['nullable', 'boolean'],
            'items.*.telegram' => ['nullable', 'boolean'],
            'items.*.email' => ['nullable', 'boolean'],
            'items.*.push' => ['nullable', 'boolean'],
            'items.*.digest' => ['nullable', 'string', 'in:instant,daily,weekly'],
        ]);
        foreach ($data['items'] as $row) {
            CrmNotificationPreference::updateOrCreate(
                ['user_id' => $request->user()->id, 'category' => $row['category']],
                array_merge($row, ['office_id' => $request->user()->office_id])
            );
        }

        return $this->notificationPreferences($request);
    }

    // --- Templates / Integrations ---
    public function templates(Request $request): JsonResponse
    {
        return response()->json([
            'data' => CrmMessageTemplate::where('office_id', $request->user()->office_id)->orderBy('name')->get(),
        ]);
    }

    public function storeTemplate(Request $request): JsonResponse
    {
        if (! $request->user()->canManageOffice()) {
            abort(403);
        }
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'channel' => ['required', 'string', 'max:30'],
            'purpose' => ['nullable', 'string', 'max:40'],
            'body' => ['required', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $tpl = CrmMessageTemplate::create([
            ...$data,
            'office_id' => $request->user()->office_id,
            'is_active' => $data['is_active'] ?? true,
        ]);

        return response()->json(['data' => $tpl], 201);
    }

    public function renderTemplate(Request $request, int $id): JsonResponse
    {
        $vars = $request->validate([
            'vars' => ['nullable', 'array'],
        ]);

        return response()->json([
            'data' => $this->comm->renderTemplate($request->user(), $id, $vars['vars'] ?? []),
        ]);
    }

    public function integrations(Request $request): JsonResponse
    {
        $this->comm->ensureIntegrationStubs((int) $request->user()->office_id);
        $items = CrmIntegration::where('office_id', $request->user()->office_id)->get()
            ->map(function (CrmIntegration $i) {
                $arr = $i->toArray();
                // Never expose credentials in list
                unset($arr['credentials']);
                $arr['has_credentials'] = ! empty($i->credentials);

                return $arr;
            });

        return response()->json(['data' => $items]);
    }

    // --- Custom fields ---
    public function customFields(Request $request): JsonResponse
    {
        $entity = $request->query('entity');

        return response()->json([
            'data' => CrmCustomField::where('office_id', $request->user()->office_id)
                ->when($entity, fn ($q) => $q->where('entity', $entity))
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get(),
        ]);
    }

    public function storeCustomField(Request $request): JsonResponse
    {
        if (! $request->user()->canManageOffice()) {
            abort(403);
        }
        $data = $request->validate([
            'entity' => ['required', 'string', 'in:lead,customer,property,deal'],
            'key' => ['required', 'string', 'max:60', 'regex:/^[a-z0-9_]+$/'],
            'label' => ['required', 'string', 'max:120'],
            'type' => ['required', 'string', 'in:text,number,select,boolean,date,textarea'],
            'options' => ['nullable', 'array'],
            'is_required' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer'],
        ]);
        $field = CrmCustomField::create([
            ...$data,
            'office_id' => $request->user()->office_id,
            'is_active' => true,
            'sort_order' => $data['sort_order'] ?? 0,
        ]);

        return response()->json(['data' => $field], 201);
    }

    public function setCustomFieldValue(Request $request): JsonResponse
    {
        $data = $request->validate([
            'custom_field_id' => ['required', 'integer'],
            'entity_type' => ['required', 'string'],
            'entity_id' => ['required', 'integer'],
            'value' => ['nullable', 'string'],
        ]);
        $field = CrmCustomField::where('office_id', $request->user()->office_id)->findOrFail($data['custom_field_id']);
        $row = CrmCustomFieldValue::updateOrCreate(
            [
                'custom_field_id' => $field->id,
                'entity_type' => $data['entity_type'],
                'entity_id' => $data['entity_id'],
            ],
            [
                'office_id' => $request->user()->office_id,
                'value' => $data['value'],
            ]
        );

        return response()->json(['data' => $row]);
    }

    // --- Saved views / onboarding ---
    public function savedViews(Request $request): JsonResponse
    {
        return response()->json([
            'data' => CrmSavedView::where('office_id', $request->user()->office_id)
                ->where(function ($q) use ($request) {
                    $q->where('user_id', $request->user()->id)->orWhere('is_shared', true);
                })->orderBy('name')->get(),
        ]);
    }

    public function storeSavedView(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'entity' => ['required', 'string', 'max:40'],
            'filters' => ['required', 'array'],
            'is_shared' => ['nullable', 'boolean'],
        ]);
        $view = CrmSavedView::create([
            ...$data,
            'office_id' => $request->user()->office_id,
            'user_id' => $request->user()->id,
            'is_shared' => $data['is_shared'] ?? false,
        ]);

        return response()->json(['data' => $view], 201);
    }

    public function onboarding(Request $request): JsonResponse
    {
        $this->comm->ensureOnboarding((int) $request->user()->office_id);

        return response()->json([
            'data' => CrmOnboardingItem::where('office_id', $request->user()->office_id)->orderBy('sort_order')->get(),
        ]);
    }

    public function toggleOnboarding(Request $request, int $id): JsonResponse
    {
        $item = CrmOnboardingItem::where('office_id', $request->user()->office_id)->findOrFail($id);
        $done = ! $item->is_done;
        $item->update([
            'is_done' => $done,
            'done_at' => $done ? now() : null,
            'done_by' => $done ? $request->user()->id : null,
        ]);

        return response()->json(['data' => $item->fresh()]);
    }

    public function bulkDealAction(Request $request): JsonResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:100'],
            'ids.*' => ['integer'],
            'action' => ['required', 'string', 'in:assign,add_tag,change_stage,archive'],
            'assigned_to' => ['nullable', 'integer'],
            'stage' => ['nullable', 'string'],
            'tag_id' => ['nullable', 'integer'],
            'lost_reason' => ['nullable', 'string'],
        ]);
        $user = $request->user();
        $deals = \App\Models\CrmDeal::where('office_id', $user->office_id)
            ->when(! $user->canManageOffice(), fn ($q) => $q->where('assigned_to', $user->id))
            ->whereIn('id', $data['ids'])
            ->get();

        $updated = 0;
        foreach ($deals as $deal) {
            if ($data['action'] === 'assign' && $user->canManageOffice() && ! empty($data['assigned_to'])) {
                $deal->update(['assigned_to' => $data['assigned_to']]);
                $updated++;
            } elseif ($data['action'] === 'change_stage' && ! empty($data['stage'])) {
                $payload = ['stage' => $data['stage']];
                if ($data['stage'] === 'closed_lost') {
                    $payload['lost_reason'] = $data['lost_reason'] ?? 'other';
                }
                $deal->update($payload);
                $updated++;
            } elseif ($data['action'] === 'archive') {
                $deal->update(['deal_status' => 'archived']);
                $updated++;
            }
        }

        return response()->json(['message' => "{$updated} مورد به‌روز شد.", 'updated' => $updated]);
    }
}
