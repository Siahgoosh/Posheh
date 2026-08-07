<?php

namespace App\Modules\Communication\Application\Services;

use App\Models\Communication\CommLead;
use App\Models\Communication\CommVisitor;

class LeadScoringService
{
    /** @return array{score: int, breakdown: array<string, int>} */
    public function calculate(array $context): array
    {
        $weights = config('communication.lead_scoring', []);
        $breakdown = [];
        $score = 0;

        if ($context['pricing_view'] ?? false) {
            $breakdown['pricing_page'] = $weights['pricing_page'] ?? 25;
            $score += $breakdown['pricing_page'];
        }
        if ($context['demo_view'] ?? false) {
            $breakdown['demo_page'] = $weights['demo_page'] ?? 20;
            $score += $breakdown['demo_page'];
        }
        if (($context['time_on_site_seconds'] ?? 0) >= 300) {
            $breakdown['time_on_site'] = $weights['time_on_site_5min'] ?? 15;
            $score += $breakdown['time_on_site'];
        }
        if (($context['message_count'] ?? 0) > 0) {
            $breakdown['sent_message'] = $weights['sent_message'] ?? 10;
            $score += $breakdown['sent_message'];
        }
        if (($context['visit_count'] ?? 0) >= 3) {
            $breakdown['visit_count'] = $weights['visit_count_3'] ?? 10;
            $score += $breakdown['visit_count'];
        }
        if (($context['staff_count'] ?? 0) >= 5) {
            $breakdown['staff_count'] = $weights['staff_count_5'] ?? 10;
            $score += $breakdown['staff_count'];
        }
        if (! empty($context['budget'])) {
            $breakdown['budget'] = $weights['budget_provided'] ?? 15;
            $score += $breakdown['budget'];
        }
        if (($context['request_type'] ?? '') === 'demo') {
            $breakdown['demo_request'] = $weights['demo_request'] ?? 20;
            $score += $breakdown['demo_request'];
        }
        if ($context['download'] ?? false) {
            $breakdown['download'] = $weights['download'] ?? 10;
            $score += $breakdown['download'];
        }

        return ['score' => min(100, $score), 'breakdown' => $breakdown];
    }

    public function recalculateVisitor(CommVisitor $visitor): void
    {
        $session = $visitor->sessions()->where('is_online', true)->orderByDesc('id')->first();
        $hasPricing = $visitor->events()->where('event_type', 'pricing_view')->exists();
        $hasDemo = $visitor->events()->where('event_type', 'demo_view')->exists();
        $messageCount = $visitor->conversations()
            ->withCount('messages')
            ->get()
            ->sum('messages_count');

        $result = $this->calculate([
            'pricing_view' => $hasPricing,
            'demo_view' => $hasDemo,
            'time_on_site_seconds' => $session?->time_on_site_seconds ?? 0,
            'message_count' => $messageCount,
            'visit_count' => $visitor->visit_count,
        ]);

        $visitor->update([
            'lead_score' => $result['score'],
            'score_breakdown' => $result['breakdown'],
        ]);
    }

    public function recalculateLead(CommLead $lead): void
    {
        $visitor = $lead->visitor;
        $session = $visitor?->sessions()->orderByDesc('id')->first();

        $result = $this->calculate([
            'pricing_view' => $visitor?->events()->where('event_type', 'pricing_view')->exists(),
            'demo_view' => $visitor?->events()->where('event_type', 'demo_view')->exists(),
            'time_on_site_seconds' => $session?->time_on_site_seconds ?? 0,
            'message_count' => $lead->conversation?->messages()->count() ?? 0,
            'visit_count' => $visitor?->visit_count ?? 1,
            'staff_count' => $lead->staff_count ?? 0,
            'budget' => $lead->budget,
            'request_type' => $lead->request_type,
        ]);

        $lead->update([
            'lead_score' => $result['score'],
            'score_breakdown' => $result['breakdown'],
        ]);

        if ($visitor) {
            $visitor->update([
                'lead_score' => $result['score'],
                'score_breakdown' => $result['breakdown'],
            ]);
        }
    }
}
