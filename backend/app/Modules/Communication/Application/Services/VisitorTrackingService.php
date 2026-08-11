<?php

namespace App\Modules\Communication\Application\Services;

use App\Models\Communication\CommVisitor;
use App\Models\Communication\CommVisitorEvent;
use App\Models\Communication\CommVisitorSession;
use App\Modules\Communication\Application\DTOs\VisitorInitDTO;
use App\Modules\Communication\Domain\Enums\VisitorEventType;
use Illuminate\Support\Str;

class VisitorTrackingService
{
    public function __construct(
        private readonly LeadScoringService $scoring,
    ) {}

    /** @return array{visitor_token: string, session_id: int, is_new: bool} */
    public function init(VisitorInitDTO $dto): array
    {
        $visitor = $dto->visitorToken
            ? CommVisitor::where('uuid', $dto->visitorToken)->first()
            : null;

        $isNew = ! $visitor;
        $browserMeta = $this->parseUserAgent($dto->userAgent);

        if (! $visitor) {
            $visitor = CommVisitor::create([
                'uuid' => (string) Str::uuid(),
                'user_id' => $dto->userId,
                'ip' => $dto->ip,
                'language' => $dto->language,
                'timezone' => $dto->timezone,
                'browser' => $browserMeta['browser'],
                'os' => $browserMeta['os'],
                'device' => $browserMeta['device'],
                'user_agent' => Str::limit($dto->userAgent ?? '', 500),
                'screen_resolution' => $dto->screenResolution,
                'landing_page' => $dto->landingPage,
                'referrer' => $dto->referrer,
                'utm_source' => $dto->utmSource,
                'utm_campaign' => $dto->utmCampaign,
                'utm_medium' => $dto->utmMedium,
                'utm_term' => $dto->utmTerm,
                'utm_content' => $dto->utmContent,
                'visit_count' => 1,
                'first_visit_at' => now(),
                'last_visit_at' => now(),
            ]);
        } else {
            $visitor->increment('visit_count');
            $visitor->update([
                'last_visit_at' => now(),
                'user_id' => $dto->userId ?? $visitor->user_id,
            ]);
        }

        $session = $this->upsertSession($visitor, $dto);

        $this->recordEvent($visitor->id, $session->id, VisitorEventType::PageView->value, $dto->currentPage);

        if (str_contains($dto->currentPage, 'pricing') || str_contains($dto->currentPage, 'subscription')) {
            $this->recordEvent($visitor->id, $session->id, VisitorEventType::PricingView->value, $dto->currentPage);
        }

        $this->scoring->recalculateVisitor($visitor);

        return [
            'visitor_token' => $visitor->uuid,
            'session_id' => $session->id,
            'is_new' => $isNew,
        ];
    }

    /** @param array<string, mixed> $payload */
    public function heartbeat(string $visitorToken, string $sessionKey, array $payload): void
    {
        $visitor = CommVisitor::where('uuid', $visitorToken)->firstOrFail();
        $session = CommVisitorSession::where('visitor_id', $visitor->id)
            ->where('session_key', $sessionKey)
            ->firstOrFail();

        $pages = $session->pages_viewed ?? [];
        $current = $payload['current_page'] ?? $session->current_page;
        if ($current && ! in_array($current, $pages, true)) {
            $pages[] = $current;
        }

        $session->update([
            'current_page' => $current,
            'pages_viewed' => $pages,
            'time_on_site_seconds' => (int) ($payload['time_on_site_seconds'] ?? $session->time_on_site_seconds),
            'scroll_depth' => max($session->scroll_depth, (int) ($payload['scroll_depth'] ?? 0)),
            'click_count' => $session->click_count + (int) ($payload['click_count_delta'] ?? 0),
            'mouse_movement_count' => $session->mouse_movement_count + (int) ($payload['mouse_movement_delta'] ?? 0),
            'is_online' => true,
            'last_activity_at' => now(),
        ]);

        $this->scoring->recalculateVisitor($visitor->fresh());
    }

    /** @param array<string, mixed> $meta */
    public function trackEvent(string $visitorToken, string $sessionKey, string $eventType, ?string $path, array $meta = []): void
    {
        $visitor = CommVisitor::where('uuid', $visitorToken)->firstOrFail();
        $session = CommVisitorSession::where('visitor_id', $visitor->id)
            ->where('session_key', $sessionKey)
            ->first();

        $this->recordEvent($visitor->id, $session?->id, $eventType, $path, $meta);

        if ($eventType === VisitorEventType::DemoView->value) {
            $this->scoring->recalculateVisitor($visitor);
        }
    }

    private function upsertSession(CommVisitor $visitor, VisitorInitDTO $dto): CommVisitorSession
    {
        $session = CommVisitorSession::where('visitor_id', $visitor->id)
            ->where('session_key', $dto->sessionKey)
            ->first();

        if ($session) {
            $session->update([
                'current_page' => $dto->currentPage,
                'is_online' => true,
                'last_activity_at' => now(),
            ]);

            return $session;
        }

        return CommVisitorSession::create([
            'visitor_id' => $visitor->id,
            'session_key' => $dto->sessionKey,
            'current_page' => $dto->currentPage,
            'pages_viewed' => [$dto->currentPage],
            'is_online' => true,
            'started_at' => now(),
            'last_activity_at' => now(),
        ]);
    }

  private function recordEvent(int $visitorId, ?int $sessionId, string $type, ?string $path, array $meta = []): void
    {
        CommVisitorEvent::create([
            'visitor_id' => $visitorId,
            'session_id' => $sessionId,
            'event_type' => $type,
            'path' => $path ? Str::limit($path, 500) : null,
            'meta' => $meta ?: null,
            'created_at' => now(),
        ]);
    }

    /** @return array{browser: string, os: string, device: string} */
    private function parseUserAgent(?string $ua): array
    {
        if (! $ua) {
            return ['browser' => 'unknown', 'os' => 'unknown', 'device' => 'desktop'];
        }

        if (class_exists(\Jenssegers\Agent\Agent::class)) {
            $agent = new \Jenssegers\Agent\Agent();
            $agent->setUserAgent($ua);

            return [
                'browser' => $agent->browser() ?: 'unknown',
                'os' => $agent->platform() ?: 'unknown',
                'device' => $agent->isMobile() ? 'mobile' : ($agent->isTablet() ? 'tablet' : 'desktop'),
            ];
        }

        $device = str_contains(strtolower($ua), 'mobile') ? 'mobile' : 'desktop';

        return ['browser' => 'unknown', 'os' => 'unknown', 'device' => $device];
    }
}
