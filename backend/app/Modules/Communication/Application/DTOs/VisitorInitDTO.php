<?php

namespace App\Modules\Communication\Application\DTOs;

readonly class VisitorInitDTO
{
    public function __construct(
        public ?string $visitorToken,
        public string $sessionKey,
        public string $currentPage,
        public ?string $referrer,
        public ?string $landingPage,
        public ?string $language,
        public ?string $timezone,
        public ?string $screenResolution,
        public ?string $utmSource,
        public ?string $utmCampaign,
        public ?string $utmMedium,
        public ?string $utmTerm,
        public ?string $utmContent,
        public ?int $userId,
        public string $ip,
        public ?string $userAgent,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromRequest(array $data, string $ip, ?string $userAgent, ?int $userId): self
    {
        return new self(
            visitorToken: $data['visitor_token'] ?? null,
            sessionKey: $data['session_key'],
            currentPage: $data['current_page'] ?? '/',
            referrer: $data['referrer'] ?? null,
            landingPage: $data['landing_page'] ?? $data['current_page'] ?? null,
            language: $data['language'] ?? null,
            timezone: $data['timezone'] ?? null,
            screenResolution: $data['screen_resolution'] ?? null,
            utmSource: $data['utm_source'] ?? null,
            utmCampaign: $data['utm_campaign'] ?? null,
            utmMedium: $data['utm_medium'] ?? null,
            utmTerm: $data['utm_term'] ?? null,
            utmContent: $data['utm_content'] ?? null,
            userId: $userId,
            ip: $ip,
            userAgent: $userAgent,
        );
    }
}
