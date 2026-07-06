<?php

namespace App\Http\Controllers\Api\Crm;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Deal;
use App\Services\Crm\CrmService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CrmController extends Controller
{
    public function __construct(private readonly CrmService $crm) {}

    public function pipeline(Request $request): JsonResponse
    {
        $pipeline = $this->crm->ensureDefaultPipeline($request->user()->office_id);

        $deals = Deal::with(['contact', 'property:id,code,title', 'assignee:id,name', 'stage'])
            ->where('office_id', $request->user()->office_id)
            ->where('status', 'open')
            ->get()
            ->groupBy('pipeline_stage_id');

        return response()->json([
            'data' => [
                'pipeline' => $pipeline,
                'deals_by_stage' => $deals,
            ],
        ]);
    }

    public function storeDeal(Request $request): JsonResponse
    {
        $data = $request->validate([
            'contact_id' => ['required', 'exists:contacts,id'],
            'property_id' => ['nullable', 'exists:properties,id'],
            'title' => ['required', 'string', 'max:255'],
            'value' => ['nullable', 'integer', 'min:0'],
            'pipeline_stage_id' => ['nullable', 'exists:pipeline_stages,id'],
            'notes' => ['nullable', 'string'],
        ]);

        $deal = $this->crm->createDeal($request->user(), $data);

        return response()->json(['data' => $deal->load(['contact', 'stage'])], 201);
    }

    public function updateDeal(Request $request, int $id): JsonResponse
    {
        $deal = Deal::where('office_id', $request->user()->office_id)->findOrFail($id);

        $data = $request->validate([
            'pipeline_stage_id' => ['sometimes', 'exists:pipeline_stages,id'],
            'status' => ['sometimes', 'in:open,won,lost'],
            'value' => ['sometimes', 'integer', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        if (($data['status'] ?? null) === 'won') {
            $data['won_at'] = now();
        }
        if (($data['status'] ?? null) === 'lost') {
            $data['lost_at'] = now();
        }

        $deal->update($data);

        return response()->json(['data' => $deal->fresh(['contact', 'stage'])]);
    }

    public function contactDetail(Request $request, int $id): JsonResponse
    {
        $contact = Contact::where('office_id', $request->user()->office_id)
            ->with(['creator:id,name', 'assignee:id,name'])
            ->findOrFail($id);

        $activities = $contact->activities()->with('user:id,name')->latest()->limit(50)->get();
        $deals = Deal::where('contact_id', $contact->id)->with('stage')->latest()->get();

        return response()->json([
            'data' => $contact,
            'activities' => $activities,
            'deals' => $deals,
        ]);
    }

    public function addActivity(Request $request, int $contactId): JsonResponse
    {
        $contact = Contact::where('office_id', $request->user()->office_id)->findOrFail($contactId);

        $data = $request->validate([
            'type' => ['required', 'in:call,sms,meeting,note,visit,email'],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['nullable', 'string'],
        ]);

        $activity = $this->crm->logActivity(
            $request->user(),
            $contact,
            $data['type'],
            $data['subject'],
            $data['body'] ?? null
        );

        $contact->update(['last_contact_at' => now()]);

        return response()->json(['data' => $activity->load('user:id,name')], 201);
    }
}
