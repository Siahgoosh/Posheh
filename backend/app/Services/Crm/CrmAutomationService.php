<?php

namespace App\Services\Crm;

use App\Models\CrmAutomationLog;
use App\Models\CrmAutomationRule;
use App\Models\CrmDeal;
use App\Models\CrmDealChecklistItem;
use App\Models\CrmFollowUp;
use App\Models\CrmOffer;
use App\Models\PropertyVisit;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

class CrmAutomationService
{
    public function __construct(
        private readonly CrmBootstrapService $bootstrap,
    ) {}

    public function ensureDefaultRules(int $officeId): void
    {
        if (! Schema::hasTable('crm_automation_rules')) {
            return;
        }

        $defaults = [
            [
                'name' => 'تماس سریع پس از Lead جدید',
                'trigger' => 'lead_created',
                'conditions' => [],
                'actions' => [
                    ['type' => 'create_follow_up', 'params' => ['minutes' => 15, 'notes' => 'تماس اولیه با سرنخ جدید']],
                    ['type' => 'create_task', 'params' => ['title' => 'تماس ظرف ۱۵ دقیقه', 'priority' => 'high']],
                ],
            ],
            [
                'name' => 'Lead داغ — اولویت بالا',
                'trigger' => 'score_changed',
                'conditions' => [['field' => 'lead_score', 'op' => '>', 'value' => 80]],
                'actions' => [
                    ['type' => 'set_priority', 'params' => ['priority' => 'urgent']],
                    ['type' => 'create_follow_up', 'params' => ['minutes' => 30, 'notes' => 'پیگیری فوری Lead داغ']],
                ],
            ],
            [
                'name' => 'پیگیری روز بعد بازدید',
                'trigger' => 'visit_completed',
                'conditions' => [],
                'actions' => [
                    ['type' => 'create_follow_up', 'params' => ['minutes' => 1440, 'notes' => 'پیگیری پس از بازدید']],
                ],
            ],
            [
                'name' => 'وظیفه مذاکره پس از Offer',
                'trigger' => 'offer_created',
                'conditions' => [],
                'actions' => [
                    ['type' => 'create_follow_up', 'params' => ['minutes' => 60, 'notes' => 'پیگیری پیشنهاد قیمت']],
                ],
            ],
            [
                'name' => 'بدون فعالیت ۷ روز',
                'trigger' => 'no_activity',
                'conditions' => [['field' => 'days_inactive', 'op' => '>=', 'value' => 7]],
                'actions' => [
                    ['type' => 'create_follow_up', 'params' => ['minutes' => 0, 'notes' => 'پیگیری معوق — ۷ روز بدون فعالیت']],
                ],
            ],
        ];

        foreach ($defaults as $row) {
            CrmAutomationRule::query()->firstOrCreate(
                ['office_id' => $officeId, 'name' => $row['name']],
                [
                    'trigger' => $row['trigger'],
                    'conditions' => $row['conditions'],
                    'actions' => $row['actions'],
                    'delay_minutes' => 0,
                    'is_active' => true,
                    'is_system' => true,
                ]
            );
        }
    }

    public function dispatch(User $user, string $trigger, object $subject, array $context = []): void
    {
        if (! Schema::hasTable('crm_automation_rules')) {
            return;
        }
        $this->ensureDefaultRules((int) $user->office_id);

        $rules = CrmAutomationRule::where('office_id', $user->office_id)
            ->where('trigger', $trigger)
            ->where('is_active', true)
            ->get();

        foreach ($rules as $rule) {
            if (! $this->conditionsPass($rule->conditions ?? [], $subject, $context)) {
                continue;
            }
            $taken = [];
            foreach ($rule->actions ?? [] as $action) {
                $taken[] = $this->runAction($user, $action, $subject, $context);
            }
            CrmAutomationLog::create([
                'office_id' => $user->office_id,
                'automation_rule_id' => $rule->id,
                'trigger' => $trigger,
                'subject_type' => $subject::class,
                'subject_id' => $subject->id ?? null,
                'reason' => ['context' => $context, 'rule' => $rule->name],
                'actions_taken' => $taken,
            ]);
        }
    }

    private function conditionsPass(array $conditions, object $subject, array $context): bool
    {
        foreach ($conditions as $cond) {
            $field = $cond['field'] ?? null;
            $op = $cond['op'] ?? '=';
            $value = $cond['value'] ?? null;
            $actual = $context[$field] ?? ($subject->{$field} ?? null);
            $ok = match ($op) {
                '>' => (float) $actual > (float) $value,
                '>=' => (float) $actual >= (float) $value,
                '<' => (float) $actual < (float) $value,
                '=' => $actual == $value,
                default => true,
            };
            if (! $ok) {
                return false;
            }
        }

        return true;
    }

    private function runAction(User $user, array $action, object $subject, array $context): array
    {
        $type = $action['type'] ?? '';
        $params = $action['params'] ?? [];

        try {
            if ($type === 'create_follow_up' && $subject instanceof CrmDeal) {
                $minutes = (int) ($params['minutes'] ?? 60);
                CrmFollowUp::create([
                    'office_id' => $user->office_id,
                    'crm_deal_id' => $subject->id,
                    'customer_id' => $subject->customer_id,
                    'assigned_to' => $subject->assigned_to ?? $user->id,
                    'created_by' => $user->id,
                    'due_at' => now()->addMinutes($minutes),
                    'status' => 'pending',
                    'priority' => $subject->priority === 'urgent' ? 'urgent' : 'normal',
                    'notes' => $params['notes'] ?? 'پیگیری خودکار',
                ]);
                $subject->update(['follow_up_at' => now()->addMinutes($minutes)]);

                return ['type' => $type, 'ok' => true];
            }
            if ($type === 'set_priority' && $subject instanceof CrmDeal) {
                $subject->update(['priority' => $params['priority'] ?? 'high']);

                return ['type' => $type, 'ok' => true];
            }
            if ($type === 'create_task' && class_exists(\App\Models\Task::class)) {
                \App\Models\Task::create([
                    'office_id' => $user->office_id,
                    'assigned_to' => ($subject->assigned_to ?? null) ?: $user->id,
                    'created_by' => $user->id,
                    'crm_deal_id' => $subject instanceof CrmDeal ? $subject->id : null,
                    'title' => $params['title'] ?? 'وظیفه CRM',
                    'priority' => $params['priority'] ?? 'medium',
                    'status' => 'pending',
                    'due_at' => now()->addHour(),
                ]);

                return ['type' => $type, 'ok' => true];
            }
        } catch (\Throwable $e) {
            return ['type' => $type, 'ok' => false, 'error' => $e->getMessage()];
        }

        return ['type' => $type, 'ok' => false, 'error' => 'unsupported'];
    }

    public function ensureDealChecklist(CrmDeal $deal): void
    {
        if (! Schema::hasTable('crm_deal_checklist_items')) {
            return;
        }
        $items = [
            ['key' => 'buyer_info', 'label' => 'اطلاعات خریدار', 'sort_order' => 10],
            ['key' => 'seller_info', 'label' => 'اطلاعات فروشنده', 'sort_order' => 20],
            ['key' => 'documents', 'label' => 'مدارک', 'sort_order' => 30],
            ['key' => 'property_check', 'label' => 'بررسی ملک', 'sort_order' => 40],
            ['key' => 'price_agreed', 'label' => 'توافق قیمت', 'sort_order' => 50],
            ['key' => 'payment_terms', 'label' => 'شرایط پرداخت', 'sort_order' => 60],
            ['key' => 'contract', 'label' => 'قرارداد', 'sort_order' => 70],
            ['key' => 'signed', 'label' => 'امضا', 'sort_order' => 80],
            ['key' => 'commission', 'label' => 'دریافت کمیسیون', 'sort_order' => 90],
            ['key' => 'settlement', 'label' => 'تسویه', 'sort_order' => 100],
        ];
        foreach ($items as $item) {
            CrmDealChecklistItem::firstOrCreate(
                ['crm_deal_id' => $deal->id, 'key' => $item['key']],
                [
                    'office_id' => $deal->office_id,
                    'label' => $item['label'],
                    'sort_order' => $item['sort_order'],
                    'is_done' => false,
                ]
            );
        }
    }
}
