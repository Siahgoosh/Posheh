<?php

namespace App\Modules\Communication\Application\DTOs;

readonly class CaptureLeadDTO
{
    public function __construct(
        public string $visitorToken,
        public string $sessionKey,
        public string $firstName,
        public ?string $lastName,
        public string $mobile,
        public ?string $email,
        public ?string $province,
        public ?string $city,
        public ?string $officeName,
        public ?string $roleTitle,
        public ?int $staffCount,
        public ?string $activityType,
        public ?string $requestType,
        public ?string $budget,
        public ?string $description,
        public bool $mobileVerified,
        public string $sourceChannel,
        public string $ip,
        public ?string $userAgent,
        public array $trackingSnapshot,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromRequest(array $data, string $ip, ?string $userAgent): self
    {
        return new self(
            visitorToken: $data['visitor_token'],
            sessionKey: $data['session_key'],
            firstName: $data['first_name'],
            lastName: $data['last_name'] ?? null,
            mobile: $data['mobile'],
            email: $data['email'] ?? null,
            province: $data['province'] ?? null,
            city: $data['city'] ?? null,
            officeName: $data['office_name'] ?? null,
            roleTitle: $data['role_title'] ?? null,
            staffCount: isset($data['staff_count']) ? (int) $data['staff_count'] : null,
            activityType: $data['activity_type'] ?? null,
            requestType: $data['request_type'] ?? null,
            budget: $data['budget'] ?? null,
            description: $data['description'] ?? null,
            mobileVerified: (bool) ($data['mobile_verified'] ?? false),
            sourceChannel: $data['source_channel'] ?? 'website',
            ip: $ip,
            userAgent: $userAgent,
            trackingSnapshot: $data['tracking_snapshot'] ?? [],
        );
    }
}
