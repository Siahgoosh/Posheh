<?php

namespace App\Services\Admin;

use App\Models\OfficeVisitRequest;
use App\Models\PlatformLead;
use App\Models\VirtualTourLead;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class PlatformLeadService
{
    /** @return array{data: list<array<string, mixed>>, meta: array<string, int>} */
    public function inbox(?string $stage = null, ?string $source = null): array
    {
        $manual = PlatformLead::query()
            ->with(['office:id,name', 'property:id,code', 'assignee:id,name'])
            ->when($stage, fn ($q) => $q->where('stage', $stage))
            ->when($source && $source !== 'all', fn ($q) => $q->where('source', $source))
            ->orderByDesc('created_at')
            ->limit(200)
            ->get()
            ->map(fn (PlatformLead $lead) => $this->formatManualLead($lead));

        $tourLeads = VirtualTourLead::query()
            ->with(['tour:id,title,slug,office_id', 'tour.office:id,name'])
            ->when($source && $source !== 'tour' && $source !== 'all', fn ($q) => $q->whereRaw('1=0'))
            ->orderByDesc('created_at')
            ->limit(100)
            ->get()
            ->map(fn (VirtualTourLead $lead) => [
                'id' => 'tour-'.$lead->id,
                'kind' => 'tour',
                'source' => 'tour',
                'source_id' => $lead->id,
                'name' => $lead->name,
                'mobile' => $lead->mobile,
                'email' => null,
                'message' => $lead->message,
                'stage' => 'new',
                'office' => $lead->tour?->office ? ['name' => $lead->tour->office->name] : null,
                'context' => $lead->tour?->title,
                'context_url' => $lead->tour?->slug ? '/tour/'.$lead->tour->slug : null,
                'follow_up_at' => null,
                'created_at' => $lead->created_at?->toIso8601String(),
            ]);

        $visitRequests = OfficeVisitRequest::query()
            ->with(['office:id,name', 'property:id,code'])
            ->when($source && $source !== 'visit' && $source !== 'all', fn ($q) => $q->whereRaw('1=0'))
            ->orderByDesc('created_at')
            ->limit(100)
            ->get()
            ->map(fn (OfficeVisitRequest $req) => [
                'id' => 'visit-'.$req->id,
                'kind' => 'visit',
                'source' => 'visit',
                'source_id' => $req->id,
                'name' => $req->name,
                'mobile' => $req->mobile,
                'email' => $req->email,
                'message' => $req->message,
                'stage' => $req->status === 'done' ? 'won' : ($req->status === 'cancelled' ? 'lost' : 'new'),
                'office' => $req->office ? ['name' => $req->office->name] : null,
                'context' => $req->property?->code,
                'context_url' => null,
                'follow_up_at' => $req->preferred_date,
                'created_at' => $req->created_at?->toIso8601String(),
            ]);

        $merged = $manual
            ->concat($tourLeads)
            ->concat($visitRequests)
            ->sortByDesc('created_at')
            ->values()
            ->take(250)
            ->all();

        return [
            'data' => $merged,
            'meta' => [
                'total' => count($merged),
                'manual' => PlatformLead::count(),
                'tour_leads' => VirtualTourLead::count(),
                'visit_requests' => OfficeVisitRequest::count(),
                'new_manual' => PlatformLead::where('stage', 'new')->count(),
            ],
        ];
    }

    public function create(array $data): PlatformLead
    {
        return PlatformLead::create([
            'source' => $data['source'] ?? 'manual',
            'name' => $data['name'] ?? null,
            'mobile' => $data['mobile'] ?? null,
            'email' => $data['email'] ?? null,
            'message' => $data['message'] ?? null,
            'office_id' => $data['office_id'] ?? null,
            'property_id' => $data['property_id'] ?? null,
            'stage' => $data['stage'] ?? 'new',
            'notes' => $data['notes'] ?? null,
            'follow_up_at' => isset($data['follow_up_at']) ? Carbon::parse($data['follow_up_at']) : null,
        ]);
    }

    public function update(PlatformLead $lead, array $data): PlatformLead
    {
        $lead->update([
            'name' => $data['name'] ?? $lead->name,
            'mobile' => $data['mobile'] ?? $lead->mobile,
            'email' => $data['email'] ?? $lead->email,
            'message' => $data['message'] ?? $lead->message,
            'stage' => $data['stage'] ?? $lead->stage,
            'notes' => $data['notes'] ?? $lead->notes,
            'assigned_to' => $data['assigned_to'] ?? $lead->assigned_to,
            'follow_up_at' => array_key_exists('follow_up_at', $data)
                ? ($data['follow_up_at'] ? Carbon::parse($data['follow_up_at']) : null)
                : $lead->follow_up_at,
        ]);

        return $lead->fresh(['office', 'property', 'assignee']);
    }

    /** @return array<string, mixed> */
    private function formatManualLead(PlatformLead $lead): array
    {
        return [
            'id' => 'lead-'.$lead->id,
            'kind' => 'manual',
            'source' => $lead->source,
            'source_id' => $lead->id,
            'name' => $lead->name,
            'mobile' => $lead->mobile,
            'email' => $lead->email,
            'message' => $lead->message,
            'stage' => $lead->stage,
            'notes' => $lead->notes,
            'office' => $lead->office ? ['name' => $lead->office->name] : null,
            'context' => $lead->property?->code,
            'context_url' => null,
            'follow_up_at' => $lead->follow_up_at?->toIso8601String(),
            'assignee' => $lead->assignee ? ['name' => $lead->assignee->name] : null,
            'created_at' => $lead->created_at?->toIso8601String(),
        ];
    }
}
