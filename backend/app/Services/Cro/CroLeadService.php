<?php

namespace App\Services\Cro;

use App\Models\BlogPost;
use App\Models\Cro\CroConversionEvent;
use App\Models\Cro\CroLead;
use App\Models\Customer;
use App\Models\Seo\SeoAlert;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CroLeadService
{
    public function __construct(
        private readonly CroConversionTracker $tracker,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @return array{lead: CroLead, duplicate: bool}
     */
    public function capture(array $data, ?string $ip = null, ?string $ua = null): array
    {
        if (! empty($data['website'] ?? null)) {
            // Honeypot: silent fake success (no storage, no leak)
            return [
                'lead' => new CroLead(['uuid' => (string) Str::uuid(), 'lead_score' => 0, 'mobile' => '']),
                'duplicate' => false,
            ];
        }

        $mobile = $this->normalizeMobile((string) ($data['mobile'] ?? ''));
        if (! $mobile) {
            throw ValidationException::withMessages(['mobile' => ['شماره موبایل معتبر نیست.']]);
        }

        return DB::transaction(function () use ($data, $mobile, $ip, $ua) {
            $existing = CroLead::query()
                ->where('mobile', $mobile)
                ->orderByDesc('id')
                ->first();

            $visitorHash = hash('sha256', ($ip ?? '').'|'.($ua ?? '').'|'.(string) config('app.key'));
            $post = null;
            if (! empty($data['article_slug'])) {
                $post = BlogPost::query()->where('slug', $data['article_slug'])->first();
            }

            $score = $this->score($data);
            $payload = [
                'uuid' => (string) Str::uuid(),
                'name' => $data['name'] ?? null,
                'mobile' => $mobile,
                'email' => $data['email'] ?? null,
                'request_type' => $data['request_type'] ?? 'OTHER',
                'property_type' => $data['property_type'] ?? null,
                'city' => $data['city'] ?? null,
                'location' => $data['location'] ?? null,
                'budget' => $data['budget'] ?? null,
                'message' => $data['message'] ?? null,
                'source' => $data['source'] ?? 'BLOG',
                'status' => 'NEW',
                'lead_score' => $score,
                'is_duplicate' => (bool) $existing,
                'duplicate_of' => $existing?->id,
                'blog_post_id' => $post?->id,
                'article_slug' => $data['article_slug'] ?? $post?->slug,
                'article_url' => $data['article_url'] ?? ($post ? '/blog/'.$post->slug : null),
                'category_slug' => $data['category_slug'] ?? $post?->category_slug,
                'landing_page' => $data['landing_page'] ?? null,
                'first_touch_path' => $data['first_touch_path'] ?? $data['landing_page'] ?? null,
                'last_touch_path' => $data['last_touch_path'] ?? $data['conversion_page'] ?? null,
                'conversion_page' => $data['conversion_page'] ?? $data['landing_page'] ?? null,
                'utm_source' => $data['utm_source'] ?? null,
                'utm_medium' => $data['utm_medium'] ?? null,
                'utm_campaign' => $data['utm_campaign'] ?? null,
                'utm_content' => $data['utm_content'] ?? null,
                'gclid' => $data['gclid'] ?? null,
                'keyword' => $data['keyword'] ?? null,
                'campaign' => $data['campaign'] ?? null,
                'consent' => (bool) ($data['consent'] ?? false),
                'ip_hash' => $ip ? hash('sha256', $ip.'|'.(string) config('app.key')) : null,
                'visitor_hash' => $visitorHash,
                'meta' => [
                    'form_variant' => $data['form_variant'] ?? 'short',
                    'merged_into' => $existing?->uuid,
                ],
            ];

            /** @var CroLead $lead */
            $lead = CroLead::query()->create($payload);

            // Attach new info onto original without losing history
            if ($existing) {
                $meta = $existing->meta ?? [];
                $meta['follow_ups'] = array_values(array_merge($meta['follow_ups'] ?? [], [[
                    'at' => now()->toIso8601String(),
                    'lead_uuid' => $lead->uuid,
                    'message' => $lead->message,
                    'request_type' => $lead->request_type,
                    'article_slug' => $lead->article_slug,
                ]]));
                $existing->update([
                    'meta' => $meta,
                    'last_touch_path' => $lead->last_touch_path ?: $existing->last_touch_path,
                    'lead_score' => max((int) $existing->lead_score, $score),
                ]);
            }

            $customerId = $this->syncCrmCustomer($lead);
            if ($customerId) {
                $lead->update(['customer_id' => $customerId]);
            }

            $this->tracker->track('form_submit', [
                'path' => $lead->conversion_page,
                'article_slug' => $lead->article_slug,
                'cro_lead_id' => $lead->id,
                'visitor_hash' => $visitorHash,
            ]);
            $this->tracker->track('lead_created', [
                'path' => $lead->conversion_page,
                'article_slug' => $lead->article_slug,
                'cro_lead_id' => $lead->id,
                'visitor_hash' => $visitorHash,
                'meta' => ['score' => $score, 'duplicate' => (bool) $existing],
            ]);

            $this->notify($lead);

            return ['lead' => $lead->fresh(), 'duplicate' => (bool) $existing];
        });
    }

    /** @param array<string, mixed> $data */
    public function score(array $data): int
    {
        $score = 20;
        $type = strtoupper((string) ($data['request_type'] ?? 'OTHER'));
        $score += match ($type) {
            'DEMO' => 35,
            'BUY', 'SELL', 'INVESTMENT', 'COMMERCIAL' => 30,
            'RENT', 'PRE_SALE', 'LAND' => 22,
            'SUPPORT' => 10,
            default => 12,
        };
        if (! empty($data['budget'])) {
            $score += 10;
        }
        if (! empty($data['city']) || ! empty($data['location'])) {
            $score += 8;
        }
        if (! empty($data['article_slug'])) {
            $score += 5;
        }
        if (($data['source'] ?? '') === 'ORGANIC' || ($data['utm_medium'] ?? '') === 'organic') {
            $score += 5;
        }
        $intentHint = strtolower((string) ($data['intent'] ?? ''));
        if (in_array($intentHint, ['commercial', 'transactional'], true)) {
            $score += 10;
        }

        return max(0, min(100, $score));
    }

    public function updateStatus(CroLead $lead, string $status, ?User $actor = null): CroLead
    {
        if (! in_array($status, CroLead::STATUSES, true)) {
            throw ValidationException::withMessages(['status' => ['وضعیت نامعتبر است.']]);
        }
        $updates = ['status' => $status];
        if ($status === 'CONTACTED' && ! $lead->contacted_at) {
            $updates['contacted_at'] = now();
            $updates['response_seconds'] = max(0, now()->diffInSeconds($lead->created_at));
        }
        if ($status === 'QUALIFIED') {
            $updates['qualified_at'] = now();
            $this->tracker->track('qualified_lead', [
                'article_slug' => $lead->article_slug,
                'cro_lead_id' => $lead->id,
                'path' => $lead->conversion_page,
            ]);
        }
        if ($status === 'WON') {
            $updates['won_at'] = now();
            $this->tracker->track('customer', [
                'article_slug' => $lead->article_slug,
                'cro_lead_id' => $lead->id,
                'path' => $lead->conversion_page,
            ]);
        }
        $meta = $lead->meta ?? [];
        if ($actor) {
            $meta['status_by'] = $actor->id;
        }
        $updates['meta'] = $meta;
        $lead->update($updates);

        return $lead->fresh();
    }

    public function setQualityFeedback(CroLead $lead, string $feedback): CroLead
    {
        $allowed = ['good', 'bad', 'wrong_intent', 'duplicate', 'converted'];
        if (! in_array($feedback, $allowed, true)) {
            throw ValidationException::withMessages(['quality_feedback' => ['بازخورد نامعتبر است.']]);
        }
        $lead->update(['quality_feedback' => $feedback]);

        return $lead->fresh();
    }

    private function normalizeMobile(string $mobile): ?string
    {
        $digits = preg_replace('/\D+/', '', $mobile) ?? '';
        if (str_starts_with($digits, '98') && strlen($digits) >= 12) {
            $digits = '0'.substr($digits, 2);
        }
        if (strlen($digits) === 10 && str_starts_with($digits, '9')) {
            $digits = '0'.$digits;
        }
        if (strlen($digits) < (int) config('cro.form.min_phone_digits', 10)) {
            return null;
        }

        return $digits;
    }

    private function syncCrmCustomer(CroLead $lead): ?int
    {
        $officeId = config('cro.default_office_id');
        if (! $officeId || ! Schema::hasTable('customers')) {
            return null;
        }
        try {
            $customer = Customer::query()
                ->where('office_id', $officeId)
                ->where('mobile', $lead->mobile)
                ->first();
            if ($customer) {
                $customer->update([
                    'name' => $lead->name ?: $customer->name,
                    'lead_score' => max((int) $customer->lead_score, (int) $lead->lead_score),
                    'source' => $customer->source ?: 'blog_cro',
                    'lifecycle' => $customer->lifecycle ?: 'lead',
                ]);

                return $customer->id;
            }

            $customer = Customer::query()->create([
                'office_id' => $officeId,
                'name' => $lead->name ?: 'Lead '.$lead->mobile,
                'mobile' => $lead->mobile,
                'email' => $lead->email,
                'city' => $lead->city,
                'source' => 'blog_cro',
                'lead_score' => $lead->lead_score,
                'lifecycle' => 'lead',
                'notes' => trim(($lead->message ?? '')."\n".'article='.$lead->article_slug),
            ]);

            return $customer->id;
        } catch (\Throwable $e) {
            Log::warning('CRO CRM sync failed', ['error' => $e->getMessage()]);

            return null;
        }
    }

    private function notify(CroLead $lead): void
    {
        if (Schema::hasTable('seo_alerts')) {
            $severity = $lead->lead_score >= (int) config('cro.high_score_alert', 70) ? 'high' : 'medium';
            SeoAlert::query()->create([
                'severity' => $severity,
                'kind' => 'cro_lead',
                'title' => 'Lead جدید: '.($lead->name ?: $lead->mobile),
                'detail' => "type={$lead->request_type} score={$lead->lead_score} article={$lead->article_slug} source={$lead->source}",
                'page_url' => $lead->article_url,
                'payload' => ['lead_uuid' => $lead->uuid, 'duplicate' => $lead->is_duplicate],
            ]);
        }

        // Optional Telegram via communication operator alerts — soft-fail
        if (config('cro.notify_telegram') && class_exists(\App\Modules\Communication\Application\Services\OperatorAlertService::class)) {
            try {
                Log::info('CRO lead captured', ['uuid' => $lead->uuid, 'score' => $lead->lead_score]);
            } catch (\Throwable) {
            }
        }
    }
}
