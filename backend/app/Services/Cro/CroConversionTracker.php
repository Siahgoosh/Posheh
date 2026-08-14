<?php

namespace App\Services\Cro;

use App\Models\Cro\CroConversionEvent;
use Illuminate\Support\Facades\Schema;

class CroConversionTracker
{
    /** @param array<string, mixed> $payload */
    public function track(string $eventType, array $payload = []): void
    {
        if (! Schema::hasTable('cro_conversion_events')) {
            return;
        }
        $allowed = [
            'cta_view', 'cta_click', 'form_start', 'form_submit',
            'phone_click', 'whatsapp_click', 'telegram_click',
            'lead_created', 'qualified_lead', 'customer',
        ];
        if (! in_array($eventType, $allowed, true)) {
            return;
        }

        CroConversionEvent::query()->create([
            'event_type' => $eventType,
            'path' => $payload['path'] ?? null,
            'article_slug' => $payload['article_slug'] ?? null,
            'cro_cta_id' => $payload['cro_cta_id'] ?? null,
            'cro_lead_id' => $payload['cro_lead_id'] ?? null,
            'visitor_hash' => $payload['visitor_hash'] ?? null,
            'session_id' => $payload['session_id'] ?? null,
            'meta' => $payload['meta'] ?? null,
            'created_at' => now(),
        ]);
    }
}
