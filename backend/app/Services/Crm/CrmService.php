<?php

namespace App\Services\Crm;

use App\Models\CrmActivity;
use App\Models\CrmDeal;
use App\Models\CrmFollowUp;
use App\Models\CrmPipelineStage;
use App\Models\Customer;
use App\Models\Property;
use App\Models\User;
use App\Services\Commission\CommissionService;
use Illuminate\Validation\ValidationException;

class CrmService
{
    /** @deprecated Prefer office crm_pipeline_stages; kept for backward compatibility */
    public const STAGES = ['lead', 'contact', 'visit', 'negotiation', 'closed_won', 'closed_lost'];

    public const STAGE_LABELS = [
        'lead' => 'سرنخ',
        'contact' => 'تماس',
        'visit' => 'بازدید',
        'negotiation' => 'مذاکره',
        'closed_won' => 'موفق',
        'closed_lost' => 'ناموفق',
    ];

    public const PRIORITIES = ['low', 'medium', 'high', 'urgent'];

    public function __construct(
        private readonly CommissionService $commissionService,
        private readonly CrmBootstrapService $bootstrap,
    ) {}

    public function list(User $user)
    {
        $this->bootstrap->ensureForUser($user);

        return CrmDeal::with(['assignee', 'property', 'customer'])
            ->where('office_id', $user->office_id)
            ->when(! $user->canManageOffice(), fn ($q) => $q->where('assigned_to', $user->id))
            ->orderByDesc('updated_at')
            ->get()
            ->map(fn (CrmDeal $deal) => $this->enrichDeal($user, $deal));
    }

    public function get(User $user, int $id): CrmDeal
    {
        $this->bootstrap->ensureForUser($user);
        $deal = CrmDeal::with(['assignee', 'property', 'customer', 'tags'])
            ->where('office_id', $user->office_id)
            ->when(! $user->canManageOffice(), fn ($q) => $q->where('assigned_to', $user->id))
            ->findOrFail($id);

        return $this->enrichDeal($user, $deal);
    }

    public function create(User $user, array $data): CrmDeal
    {
        $this->bootstrap->ensureForUser($user);
        if (isset($data['stage'])) {
            $this->assertValidStage($user, $data['stage']);
        }
        $this->assertOfficeRefs($user, $data);

        $deal = CrmDeal::create(array_merge($data, [
            'office_id' => $user->office_id,
            'created_by' => $user->id,
            'assigned_to' => $data['assigned_to'] ?? $user->id,
            'stage' => $data['stage'] ?? 'lead',
            'priority' => $data['priority'] ?? 'medium',
            'lead_score' => $data['lead_score'] ?? $this->calculateLeadScore($data),
        ]));

        if (! empty($data['follow_up_at'])) {
            $this->syncDealFollowUp($user, $deal);
        }

        $this->logActivity($user, $deal, 'note', 'معامله ایجاد شد');

        return $this->enrichDeal($user, $deal->load(['assignee', 'property', 'customer']));
    }

    public function update(User $user, int $id, array $data): CrmDeal
    {
        $this->bootstrap->ensureForUser($user);
        $deal = CrmDeal::where('office_id', $user->office_id)
            ->when(! $user->canManageOffice(), fn ($q) => $q->where('assigned_to', $user->id))
            ->findOrFail($id);
        $oldStage = $deal->stage;
        $this->assertOfficeRefs($user, $data);

        if (isset($data['stage'])) {
            $this->assertValidStage($user, $data['stage']);
            if ($data['stage'] === 'closed_lost' && empty($data['lost_reason']) && empty($deal->lost_reason)) {
                throw ValidationException::withMessages([
                    'lost_reason' => ['برای معامله ناموفق، دلیل الزامی است.'],
                ]);
            }
        }

        if (isset($data['lead_score'])) {
            $data['lead_score'] = max(0, min(100, (int) $data['lead_score']));
        }

        $deal->update($data);
        $deal = $deal->fresh(['assignee', 'property', 'customer']);

        if (isset($data['stage']) && $data['stage'] !== $oldStage) {
            $fromLabel = self::STAGE_LABELS[$oldStage] ?? $oldStage;
            $toLabel = self::STAGE_LABELS[$data['stage']] ?? $data['stage'];
            $this->logActivity($user, $deal, 'stage_change', "مرحله از «{$fromLabel}» به «{$toLabel}» تغییر کرد", [
                'from' => $oldStage,
                'to' => $data['stage'],
            ]);
        }

        if (array_key_exists('follow_up_at', $data)) {
            $this->syncDealFollowUp($user, $deal);
        }

        if ($deal->stage === 'closed_won') {
            $this->commissionService->createFromDeal($user, $deal);
        }

        return $this->enrichDeal($user, $deal);
    }

    public function delete(User $user, int $id): void
    {
        $deal = CrmDeal::where('office_id', $user->office_id)
            ->when(! $user->canManageOffice(), fn ($q) => $q->where('assigned_to', $user->id))
            ->findOrFail($id);
        $deal->activities()->delete();
        $deal->delete();
    }

    public function stages(?User $user = null): array
    {
        if ($user?->office_id) {
            $this->bootstrap->ensureForUser($user);
            $rows = CrmPipelineStage::where('office_id', $user->office_id)
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get();
            if ($rows->isNotEmpty()) {
                return $rows->map(fn (CrmPipelineStage $s) => [
                    'key' => $s->key,
                    'label' => $s->label,
                    'color' => $s->color,
                    'is_won' => $s->is_won,
                    'is_lost' => $s->is_lost,
                    'sort_order' => $s->sort_order,
                ])->all();
            }
        }

        return array_map(
            fn (string $key) => ['key' => $key, 'label' => self::STAGE_LABELS[$key] ?? $key],
            self::STAGES,
        );
    }

    public static function stageLabel(?string $stage): string
    {
        return self::STAGE_LABELS[$stage ?? ''] ?? ($stage ?? '');
    }

    public function pipelineSummary(User $user): array
    {
        $this->bootstrap->ensureForUser($user);
        $stages = $this->stages($user);
        $rows = CrmDeal::where('office_id', $user->office_id)
            ->when(! $user->canManageOffice(), fn ($q) => $q->where('assigned_to', $user->id))
            ->selectRaw('stage, count(*) as count, coalesce(sum(value), 0) as total_value')
            ->groupBy('stage')
            ->get();

        $summary = [];
        foreach ($stages as $stage) {
            $key = $stage['key'];
            $row = $rows->firstWhere('stage', $key);
            $summary[$key] = [
                'stage' => $key,
                'label' => $stage['label'],
                'count' => (int) ($row->count ?? 0),
                'total_value' => (int) ($row->total_value ?? 0),
            ];
        }

        return $summary;
    }

    public function followUps(User $user)
    {
        $this->bootstrap->ensureForUser($user);

        return CrmDeal::with(['assignee', 'customer'])
            ->where('office_id', $user->office_id)
            ->when(! $user->canManageOffice(), fn ($q) => $q->where('assigned_to', $user->id))
            ->whereNotNull('follow_up_at')
            ->where('follow_up_at', '<=', now()->addDays(7))
            ->whereNotIn('stage', ['closed_won', 'closed_lost'])
            ->orderBy('follow_up_at')
            ->get()
            ->map(fn (CrmDeal $deal) => $this->enrichDeal($user, $deal));
    }

    public function activities(User $user, int $dealId)
    {
        $deal = CrmDeal::where('office_id', $user->office_id)
            ->when(! $user->canManageOffice(), fn ($q) => $q->where('assigned_to', $user->id))
            ->findOrFail($dealId);

        return $deal->activities()->with('user')->limit(50)->get();
    }

    public function addActivity(User $user, int $dealId, array $data): CrmActivity
    {
        $deal = CrmDeal::where('office_id', $user->office_id)
            ->when(! $user->canManageOffice(), fn ($q) => $q->where('assigned_to', $user->id))
            ->findOrFail($dealId);

        $activity = $this->logActivity($user, $deal, $data['type'] ?? 'note', $data['body'] ?? '', $data['meta'] ?? null);

        if (in_array($data['type'] ?? '', ['call', 'meeting', 'visit', 'note'], true)) {
            $deal->update(['last_contacted_at' => now()]);
        }

        return $activity;
    }

    /**
     * @return list<array{id:int,name:string,mobile:?string,match:string}>
     */
    public function findDuplicateCustomers(User $user, string $mobile, ?int $excludeId = null): array
    {
        $mobile = preg_replace('/\D+/', '', $mobile) ?? '';
        if (strlen($mobile) < 10) {
            return [];
        }
        $suffix = substr($mobile, -10);

        return Customer::where('office_id', $user->office_id)
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->where(function ($q) use ($suffix, $mobile) {
                $q->where('mobile', 'like', '%'.$suffix)
                    ->orWhere('mobile', $mobile)
                    ->orWhere('mobile_secondary', 'like', '%'.$suffix);
            })
            ->limit(10)
            ->get(['id', 'name', 'mobile'])
            ->map(fn (Customer $c) => [
                'id' => $c->id,
                'name' => $c->name,
                'mobile' => $c->mobile,
                'match' => 'mobile',
            ])
            ->all();
    }

    public function nextBestActions(User $user, CrmDeal $deal): array
    {
        $actions = [];
        if ($deal->follow_up_at && $deal->follow_up_at->isPast() && ! in_array($deal->stage, ['closed_won', 'closed_lost'], true)) {
            $days = (int) $deal->follow_up_at->diffInDays(now());
            $actions[] = [
                'code' => 'overdue_followup',
                'priority' => 'urgent',
                'message' => "این معامله {$days} روز است پیگیری نشده. پیشنهاد: امروز تماس بگیرید.",
            ];
        }
        if ($deal->stage === 'lead' && empty($deal->contact_mobile)) {
            $actions[] = [
                'code' => 'need_phone',
                'priority' => 'high',
                'message' => 'شماره تماس ثبت نشده. پیشنهاد: اطلاعات تماس را تکمیل کنید.',
            ];
        }
        if (in_array($deal->stage, ['contact', 'lead'], true) && ! $deal->property_id) {
            $actions[] = [
                'code' => 'match_property',
                'priority' => 'normal',
                'message' => 'هنوز فایل ملکی متصل نیست. پیشنهاد: Matching و ارسال فایل انجام دهید.',
            ];
        }
        if ($deal->stage === 'visit' && empty($deal->follow_up_at)) {
            $actions[] = [
                'code' => 'schedule_followup',
                'priority' => 'high',
                'message' => 'بعد از بازدید پیگیری تنظیم نشده. پیشنهاد: Follow-up برای فردا بگذارید.',
            ];
        }
        if ($deal->stage === 'negotiation' && empty($deal->offer_amount)) {
            $actions[] = [
                'code' => 'submit_offer',
                'priority' => 'normal',
                'message' => 'مبلغ پیشنهاد ثبت نشده. پیشنهاد: Offer را ثبت کنید.',
            ];
        }

        return $actions;
    }

    public function calculateLeadScore(array $data): int
    {
        $score = 30;

        if (! empty($data['contact_mobile'])) {
            $score += 20;
        }
        if (! empty($data['contact_name'])) {
            $score += 10;
        }
        if (! empty($data['value']) && $data['value'] > 0) {
            $score += 15;
        }
        if (! empty($data['property_id'])) {
            $score += 15;
        }
        if (! empty($data['source']) || ! empty($data['source_id'])) {
            $score += 10;
        }
        if (! empty($data['customer_id'])) {
            $score += 5;
        }

        return min(100, max(0, $score));
    }

    private function assertValidStage(User $user, string $stage): void
    {
        $keys = collect($this->stages($user))->pluck('key')->all();
        if (! in_array($stage, $keys, true) && ! in_array($stage, self::STAGES, true)) {
            throw ValidationException::withMessages([
                'stage' => ['مرحله معامله نامعتبر است.'],
            ]);
        }
    }

    private function assertOfficeRefs(User $user, array $data): void
    {
        if (! empty($data['property_id'])) {
            Property::where('office_id', $user->office_id)->findOrFail($data['property_id']);
        }
        if (! empty($data['assigned_to'])) {
            User::where('office_id', $user->office_id)->findOrFail($data['assigned_to']);
        }
        if (! empty($data['customer_id'])) {
            Customer::where('office_id', $user->office_id)->findOrFail($data['customer_id']);
        }
    }

    private function syncDealFollowUp(User $user, CrmDeal $deal): void
    {
        if (! $deal->follow_up_at) {
            return;
        }
        if (! \Illuminate\Support\Facades\Schema::hasTable('crm_follow_ups')) {
            return;
        }
        try {
            CrmFollowUp::query()->updateOrCreate(
                [
                    'office_id' => $deal->office_id,
                    'crm_deal_id' => $deal->id,
                    'status' => 'pending',
                ],
                [
                    'customer_id' => $deal->customer_id,
                    'assigned_to' => $deal->assigned_to ?? $user->id,
                    'created_by' => $user->id,
                    'due_at' => $deal->follow_up_at,
                    'priority' => $deal->priority === 'urgent' ? 'urgent' : 'normal',
                    'next_action' => $deal->next_action,
                    'notes' => 'پیگیری معامله: '.$deal->title,
                ]
            );
        } catch (\Throwable) {
            // Table/columns may not be migrated yet on older deploys
        }
    }

    private function enrichDeal(User $user, CrmDeal $deal): CrmDeal
    {
        $deal->setAttribute('is_overdue', $deal->follow_up_at && $deal->follow_up_at->isPast()
            && ! in_array($deal->stage, ['closed_won', 'closed_lost'], true));
        $deal->setAttribute('stage_label', self::STAGE_LABELS[$deal->stage] ?? $deal->stage);
        $deal->setAttribute('score_band', $deal->scoreBand());
        $deal->setAttribute('score_band_label', match ($deal->scoreBand()) {
            'cold' => 'سرد',
            'low' => 'کم',
            'warm' => 'گرم',
            'hot' => 'داغ',
            default => 'خیلی داغ',
        });
        $deal->setAttribute('next_best_actions', $this->nextBestActions($user, $deal));

        return $deal;
    }

    private function logActivity(User $user, CrmDeal $deal, string $type, ?string $body = null, ?array $meta = null): CrmActivity
    {
        return CrmActivity::create([
            'crm_deal_id' => $deal->id,
            'user_id' => $user->id,
            'type' => $type,
            'body' => $body,
            'meta' => $meta,
        ]);
    }
}
