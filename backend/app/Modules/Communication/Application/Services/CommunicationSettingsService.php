<?php

namespace App\Modules\Communication\Application\Services;

use App\Services\Settings\SystemSettingsService;

class CommunicationSettingsService
{
    public function __construct(private readonly SystemSettingsService $settings) {}

    public function telegramBotToken(): string
    {
        return trim((string) $this->settings->get('comm_telegram_bot_token', ''));
    }

    /** @return list<string> */
    public function telegramAlertChatIds(): array
    {
        $raw = (string) $this->settings->get('comm_telegram_alert_chat_ids', '');

        return array_values(array_filter(array_map('trim', explode(',', $raw))));
    }

    public function whatsappPhoneNumberId(): ?string
    {
        $v = trim((string) $this->settings->get('comm_whatsapp_phone_number_id', ''));

        return $v !== '' ? $v : null;
    }

    public function whatsappAccessToken(): ?string
    {
        $v = trim((string) $this->settings->get('comm_whatsapp_access_token', ''));

        return $v !== '' ? $v : null;
    }

    public function emailFromAddress(): ?string
    {
        $v = trim((string) $this->settings->get('comm_email_from', ''));

        return $v !== '' ? $v : null;
    }

    public function emailFromName(): string
    {
        return trim((string) $this->settings->get('comm_email_from_name', 'پشتیبانی پوشه'));
    }

    public function emailInboundDomain(): string
    {
        return trim((string) $this->settings->get('comm_email_inbound_domain', 'support.posheapp.ir'));
    }

    public function emailWebhookSecret(): ?string
    {
        $v = trim((string) $this->settings->get('comm_email_webhook_secret', ''));

        return $v !== '' ? $v : null;
    }

    public function aiProvider(): string
    {
        return trim((string) $this->settings->get('comm_ai_provider', 'internal')) ?: 'internal';
    }

    public function aiOpenaiKey(): ?string
    {
        $v = trim((string) $this->settings->get('comm_ai_openai_key', ''));

        return $v !== '' ? $v : null;
    }

    public function aiOpenaiModel(): string
    {
        return trim((string) $this->settings->get('comm_ai_openai_model', 'gpt-4o-mini'));
    }

    /** @return array<string, mixed> */
    public function adminStatus(): array
    {
        $baseUrl = rtrim((string) config('app.url'), '/');

        return [
            'telegram_configured' => $this->telegramBotToken() !== '',
            'whatsapp_configured' => $this->whatsappPhoneNumberId() && $this->whatsappAccessToken(),
            'email_from_configured' => (bool) $this->emailFromAddress(),
            'ai_provider' => $this->aiProvider(),
            'ai_openai_configured' => (bool) $this->aiOpenaiKey(),
            'email_inbound_domain' => $this->emailInboundDomain(),
            'telegram_webhook_url' => $baseUrl.'/api/v1/communication/telegram/webhook',
            'email_inbound_url' => $baseUrl.'/api/v1/communication/email/inbound',
        ];
    }
}
