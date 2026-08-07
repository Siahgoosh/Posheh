<?php

namespace App\Modules\Communication\Application\Services;

use App\Models\Communication\CommLead;
use App\Models\Communication\CommPipelineStage;
use App\Models\Communication\CommVisitor;
use App\Modules\Communication\Application\DTOs\CaptureLeadDTO;
use App\Modules\Communication\Domain\Enums\LeadStatus;
use Illuminate\Support\Facades\DB;

class LeadCaptureService
{
    public function __construct(
        private readonly LeadScoringService $scoring,
        private readonly ConversationService $conversations,
        private readonly OperatorAlertService $alerts,
    ) {}

    /** @return array{lead: CommLead, conversation_uuid: string} */
    public function capture(CaptureLeadDTO $dto): array
    {
        return DB::transaction(function () use ($dto) {
            $visitor = CommVisitor::where('uuid', $dto->visitorToken)->firstOrFail();

            $visitor->update([
                'first_name' => $dto->firstName,
                'last_name' => $dto->lastName,
                'mobile' => $dto->mobile,
                'email' => $dto->email,
                'province' => $dto->province,
                'city' => $dto->city,
            ]);

            $defaultStage = CommPipelineStage::query()
                ->whereHas('pipeline', fn ($q) => $q->where('is_default', true))
                ->orderBy('sort_order')
                ->first();

        $lead = CommLead::create([
            'visitor_id' => $visitor->id,
            'pipeline_stage_id' => $defaultStage?->id,
            'first_name' => $dto->firstName,
            'last_name' => $dto->lastName,
            'mobile' => $dto->mobile,
            'mobile_verified' => $dto->mobileVerified,
            'email' => $dto->email,
            'province' => $dto->province,
            'city' => $dto->city,
            'office_name' => $dto->officeName,
            'role_title' => $dto->roleTitle,
            'staff_count' => $dto->staffCount,
            'activity_type' => $dto->activityType,
            'request_type' => $dto->requestType,
            'budget' => $dto->budget,
            'description' => $dto->description,
            'source_channel' => $dto->sourceChannel,
            'status' => LeadStatus::New->value,
            'ip' => $dto->ip,
            'tracking_snapshot' => $dto->trackingSnapshot,
        ]);

            $lead->load('visitor');
            $this->scoring->recalculateLead($lead);

            $conversation = $this->conversations->createForLead($visitor, $lead, $dto->sourceChannel);

            $this->alerts->notifyNewConversation($conversation->fresh(['lead', 'visitor']));

            return [
                'lead' => $lead->fresh(['stage', 'visitor']),
                'conversation_uuid' => $conversation->uuid,
            ];
        });
    }
}
