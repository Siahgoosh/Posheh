<?php

namespace App\Services\Crm;

use App\Models\CrmIntegration;
use App\Models\CrmIntegrationLog;
use App\Models\CrmMessageTemplate;
use App\Models\CrmNotification;
use App\Models\CrmNotificationPreference;
use App\Models\CrmOnboardingItem;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

/**
 * Communication + notifications + integrations architecture (adapters, no fake APIs).
 */
class CrmCommunicationService
{
    public function notify(User $actor, User $recipient, string $category, string $title, ?string $body = null, ?string $link = null, array $meta = []): ?CrmNotification
    {
        if (! Schema::hasTable('crm_notifications')) {
            return null;
        }
        $prefs = Schema::hasTable('crm_notification_preferences')
            ? CrmNotificationPreference::where('user_id', $recipient->id)->where('category', $category)->first()
            : null;
        if ($prefs && ! $prefs->in_app) {
            return null;
        }

        return CrmNotification::create([
            'office_id' => $recipient->office_id,
            'user_id' => $recipient->id,
            'category' => $category,
            'title' => $title,
            'body' => $body,
            'link' => $link,
            'meta' => $meta,
        ]);
    }

    public function listNotifications(User $user, bool $unreadOnly = false)
    {
        $this->purgeExpired();

        return CrmNotification::where('office_id', $user->office_id)
            ->where('user_id', $user->id)
            ->where('created_at', '>=', now()->subHours(48))
            ->when($unreadOnly, fn ($q) => $q->whereNull('read_at'))
            ->orderByDesc('id')
            ->limit(50)
            ->get();
    }

    public function markRead(User $user, int $id): CrmNotification
    {
        $n = CrmNotification::where('office_id', $user->office_id)->where('user_id', $user->id)->findOrFail($id);
        $n->update(['read_at' => now()]);

        return $n->fresh();
    }

    public function markAllRead(User $user): int
    {
        return CrmNotification::where('office_id', $user->office_id)
            ->where('user_id', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    public function purgeExpired(): int
    {
        if (! Schema::hasTable('crm_notifications')) {
            return 0;
        }

        return CrmNotification::where('created_at', '<', now()->subHours(48))->delete();
    }

    public function ensureDefaultPreferences(User $user): void
    {
        if (! Schema::hasTable('crm_notification_preferences')) {
            return;
        }
        foreach (['sales', 'crm', 'tasks', 'followups', 'viewings', 'offers', 'deals', 'finance', 'system'] as $cat) {
            CrmNotificationPreference::firstOrCreate(
                ['user_id' => $user->id, 'category' => $cat],
                [
                    'office_id' => $user->office_id,
                    'in_app' => true,
                    'sms' => false,
                    'telegram' => in_array($cat, ['followups', 'viewings', 'system'], true),
                    'email' => false,
                    'push' => true,
                    'digest' => 'instant',
                ]
            );
        }
    }

    public function ensureIntegrationStubs(int $officeId): void
    {
        if (! Schema::hasTable('crm_integrations')) {
            return;
        }
        foreach ([
            ['provider' => 'sms', 'name' => 'SMS Provider'],
            ['provider' => 'whatsapp', 'name' => 'WhatsApp Business'],
            ['provider' => 'telegram', 'name' => 'Telegram Bot'],
            ['provider' => 'email', 'name' => 'Email SMTP'],
        ] as $row) {
            CrmIntegration::firstOrCreate(
                ['office_id' => $officeId, 'provider' => $row['provider'], 'name' => $row['name']],
                ['is_enabled' => false, 'status' => 'disconnected', 'settings' => []]
            );
        }
    }

    public function renderTemplate(User $user, int $templateId, array $vars): array
    {
        $tpl = CrmMessageTemplate::where('office_id', $user->office_id)->findOrFail($templateId);

        return [
            'channel' => $tpl->channel,
            'body' => $tpl->render($vars),
            'template_id' => $tpl->id,
        ];
    }

    public function logIntegration(int $officeId, string $provider, string $status, array $meta = [], ?int $integrationId = null, ?string $error = null): void
    {
        if (! Schema::hasTable('crm_integration_logs')) {
            return;
        }
        CrmIntegrationLog::create([
            'office_id' => $officeId,
            'integration_id' => $integrationId,
            'provider' => $provider,
            'direction' => 'outbound',
            'status' => $status,
            'payload_meta' => $meta,
            'error' => $error,
        ]);
    }

    public function ensureOnboarding(int $officeId): void
    {
        if (! Schema::hasTable('crm_onboarding_checklist')) {
            return;
        }
        $items = [
            ['key' => 'add_agents', 'label' => 'افزودن مشاوران', 'sort_order' => 10],
            ['key' => 'lead_sources', 'label' => 'پیکربندی منابع Lead', 'sort_order' => 20],
            ['key' => 'pipeline', 'label' => 'تنظیم Pipeline', 'sort_order' => 30],
            ['key' => 'import_contacts', 'label' => 'ورود مخاطبین', 'sort_order' => 40],
            ['key' => 'import_properties', 'label' => 'ورود فایل‌ها', 'sort_order' => 50],
            ['key' => 'followup', 'label' => 'پیکربندی Follow-up', 'sort_order' => 60],
            ['key' => 'notifications', 'label' => 'تنظیم اعلان‌ها', 'sort_order' => 70],
        ];
        foreach ($items as $item) {
            CrmOnboardingItem::firstOrCreate(
                ['office_id' => $officeId, 'key' => $item['key']],
                ['label' => $item['label'], 'sort_order' => $item['sort_order'], 'is_done' => false]
            );
        }
    }
}
