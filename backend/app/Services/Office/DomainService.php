<?php

namespace App\Services\Office;

use App\Models\DomainOrder;
use App\Models\Office;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class DomainService
{
    public const IR_DOMAIN_PRICE = 450_000;

    public function status(Office $office): array
    {
        $order = DomainOrder::where('office_id', $office->id)->latest()->first();

        return [
            'custom_domain' => $office->custom_domain,
            'custom_domain_status' => $office->custom_domain_status ?? 'none',
            'dns_token' => $office->domain_dns_token,
            'dns_instructions' => $this->dnsInstructions($office),
            'latest_order' => $order ? [
                'id' => $order->id,
                'domain_name' => $order->domain_name,
                'status' => $order->status,
                'price' => $order->price,
                'created_at' => $order->created_at?->toIso8601String(),
            ] : null,
            'ir_domain_price' => self::IR_DOMAIN_PRICE,
        ];
    }

    public function orderDomain(User $user, string $domainName): DomainOrder
    {
        $office = $user->office;
        if (! $office) {
            throw ValidationException::withMessages(['domain' => ['دفتر یافت نشد.']]);
        }

        if (! $user->canManageOffice()) {
            throw ValidationException::withMessages(['domain' => ['فقط مدیر دفتر می‌تواند دامنه سفارش دهد.']]);
        }

        $domainName = strtolower(trim($domainName));
        if (! preg_match('/^[a-z0-9]([a-z0-9-]*[a-z0-9])?\.ir$/', $domainName)) {
            throw ValidationException::withMessages([
                'domain_name' => ['دامنه باید با .ir تمام شود (مثال: myoffice.ir)'],
            ]);
        }

        $pending = DomainOrder::where('office_id', $office->id)
            ->whereIn('status', ['pending', 'paid', 'purchasing'])
            ->exists();

        if ($pending) {
            throw ValidationException::withMessages([
                'domain_name' => ['یک سفارش دامنه در حال پردازش دارید.'],
            ]);
        }

        return DomainOrder::create([
            'office_id' => $office->id,
            'requested_by' => $user->id,
            'domain_name' => $domainName,
            'status' => 'pending',
            'price' => self::IR_DOMAIN_PRICE,
        ]);
    }

    public function connectDomain(User $user, string $domainName): Office
    {
        $office = $user->office;
        if (! $office || ! $user->canManageOffice()) {
            throw ValidationException::withMessages(['domain' => ['دسترسی ندارید.']]);
        }

        $domainName = strtolower(trim($domainName));
        if (! preg_match('/^[a-z0-9]([a-z0-9-]*[a-z0-9])?(\.ir|\.com|\.net)$/', $domainName)) {
            throw ValidationException::withMessages([
                'domain_name' => ['فرمت دامنه نامعتبر است.'],
            ]);
        }

        $token = Str::random(32);
        $office->update([
            'custom_domain' => $domainName,
            'custom_domain_status' => 'dns_pending',
            'domain_dns_token' => $token,
        ]);

        return $office->fresh();
    }

    public function verifyDns(Office $office): bool
    {
        if (! $office->custom_domain || ! $office->domain_dns_token) {
            return false;
        }

        // In production, check DNS TXT/CNAME record. For now accept manual verify request.
        $office->update(['custom_domain_status' => 'verified']);

        DomainOrder::where('office_id', $office->id)
            ->where('domain_name', $office->custom_domain)
            ->where('status', 'purchased')
            ->update(['status' => 'connected', 'connected_at' => now()]);

        return true;
    }

    public function activateDomain(Office $office): void
    {
        $office->update(['custom_domain_status' => 'active']);
    }

    /** @return array<int, array<string, string>> */
    private function dnsInstructions(Office $office): array
    {
        if (! $office->custom_domain || ! $office->domain_dns_token) {
            return [];
        }

        $target = config('app.office_site_cname', 'sites.posheapp.ir');

        return [
            ['type' => 'CNAME', 'host' => '@', 'value' => $target, 'note' => 'یا A record به IP سرور پوشه'],
            ['type' => 'TXT', 'host' => '_posheh-verify', 'value' => $office->domain_dns_token, 'note' => 'تأیید مالکیت دامنه'],
        ];
    }
}
