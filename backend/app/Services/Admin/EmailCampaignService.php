<?php

namespace App\Services\Admin;

use App\Models\EmailCampaign;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EmailCampaignService
{
  public const SEGMENTS = [
        'all_managers' => 'همه مدیران دفاتر',
        'trial_offices' => 'دفاتر در دوره آزمایشی',
        'paid_offices' => 'دفاتر با اشتراک فعال',
        'all_users' => 'همه کاربران با ایمیل',
    ];

    public function list(): \Illuminate\Database\Eloquent\Collection
    {
        return EmailCampaign::query()
            ->orderByDesc('created_at')
            ->with('creator:id,name')
            ->get();
    }

    public function create(array $data, ?int $userId): EmailCampaign
    {
        return EmailCampaign::create([
            'subject' => $data['subject'],
            'body_html' => $data['body_html'],
            'body_text' => $data['body_text'] ?? strip_tags($data['body_html']),
            'segment' => $data['segment'] ?? 'all_managers',
            'status' => 'draft',
            'created_by' => $userId,
        ]);
    }

    public function update(EmailCampaign $campaign, array $data): EmailCampaign
    {
        if ($campaign->status === 'sent') {
            abort(422, 'کمپین ارسال‌شده قابل ویرایش نیست.');
        }

        $campaign->update([
            'subject' => $data['subject'] ?? $campaign->subject,
            'body_html' => $data['body_html'] ?? $campaign->body_html,
            'body_text' => $data['body_text'] ?? $campaign->body_text,
            'segment' => $data['segment'] ?? $campaign->segment,
        ]);

        return $campaign->fresh();
    }

    public function send(EmailCampaign $campaign): EmailCampaign
    {
        if ($campaign->status === 'sent') {
            return $campaign;
        }

        $recipients = $this->recipientsForSegment($campaign->segment);
        $sent = 0;
        $failed = 0;

        foreach ($recipients as $email) {
            try {
                Mail::html($campaign->body_html, function ($message) use ($email, $campaign) {
                    $message->to($email)->subject($campaign->subject);
                });
                $sent++;
            } catch (\Throwable $e) {
                $failed++;
                Log::warning('Email campaign send failed', ['email' => $email, 'error' => $e->getMessage()]);
            }
        }

        $campaign->update([
            'status' => 'sent',
            'sent_count' => $sent,
            'failed_count' => $failed,
            'sent_at' => now(),
        ]);

        return $campaign->fresh();
    }

    /** @return list<string> */
    private function recipientsForSegment(string $segment): array
    {
        $query = User::query()
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->where('is_active', true);

        if ($segment === 'all_managers') {
            $query->where('role', 'office_manager');
        } elseif ($segment === 'trial_offices') {
            $query->whereHas('office', fn ($q) => $q->whereNotNull('trial_ends_at')->where('trial_ends_at', '>', now()));
        } elseif ($segment === 'paid_offices') {
            $query->whereHas('office', fn ($q) => $q->where('plan_active', true));
        }

        return $query->pluck('email')->unique()->filter()->values()->all();
    }
}
