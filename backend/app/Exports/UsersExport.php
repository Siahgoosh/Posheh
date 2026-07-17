<?php

namespace App\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class UsersExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        return User::with(['office.plan', 'office.subscription.plan'])
            ->orderBy('id')
            ->get();
    }

    public function headings(): array
    {
        return [
            'شناسه',
            'نام',
            'موبایل',
            'نقش',
            'دفتر',
            'پلن',
            'دوره آزمایشی تا',
            'اشتراک تا',
            'دسترسی فعال',
            'تاریخ ثبت‌نام',
        ];
    }

    /** @param User $user */
    public function map($user): array
    {
        $office = $user->office;
        $sub = $office?->subscription;

        return [
            $user->id,
            $user->name,
            $user->mobile,
            $user->role?->value ?? (string) $user->role,
            $office?->name ?? '—',
            $office?->plan?->name ?? $sub?->plan?->name ?? '—',
            $office?->trial_ends_at?->format('Y-m-d H:i') ?? '—',
            $sub?->ends_at?->format('Y-m-d H:i') ?? '—',
            $office && app(\App\Services\Subscription\SubscriptionAccessService::class)->hasAccess($office) ? 'بله' : 'خیر',
            $user->created_at?->format('Y-m-d H:i'),
        ];
    }
}
