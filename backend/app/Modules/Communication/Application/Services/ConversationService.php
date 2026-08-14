<?php

namespace App\Modules\Communication\Application\Services;

use App\Models\Communication\CommConversation;
use App\Models\Communication\CommLead;
use App\Models\Communication\CommVisitor;
use App\Modules\Communication\Domain\Enums\ConversationStatus;
use Illuminate\Support\Str;

class ConversationService
{
    public function createForLead(CommVisitor $visitor, CommLead $lead, string $channel): CommConversation
    {
        return CommConversation::create([
            'uuid' => (string) Str::uuid(),
            'visitor_id' => $visitor->id,
            'lead_id' => $lead->id,
            'channel' => $channel,
            'status' => ConversationStatus::Open->value,
            'subject' => $lead->office_name ?: trim("{$lead->first_name} {$lead->last_name}"),
            'last_message_at' => now(),
        ]);
    }

    public function findByUuid(string $uuid): ?CommConversation
    {
        return CommConversation::where('uuid', $uuid)->first();
    }

    public function findByUuidForVisitor(string $uuid, string $visitorToken): ?CommConversation
    {
        return CommConversation::query()
            ->where('uuid', $uuid)
            ->whereHas('visitor', fn ($q) => $q->where('uuid', $visitorToken))
            ->first();
    }
}
