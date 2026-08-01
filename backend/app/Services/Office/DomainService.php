<?php

namespace App\Services\Office;

use App\Enums\PaymentGateway;
use App\Models\DomainOrder;
use App\Models\Office;
use App\Models\Payment;
use App\Models\User;
use App\Services\Payment\ZibalService;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class DomainService
{
    public const IR_DOMAIN_PRICE = 110_000;

    public function __construct(
        private readonly IrNicWhoisService $whois,
        private readonly ZibalService $zibal,
    ) {}

    public function status(Office $office): array
    {
        $order = DomainOrder::where('office_id', $office->id)->latest()->first();

        return [
            'custom_domain' => $office->custom_domain,
            'custom_domain_status' => $office->custom_domain_status ?? 'none',
            'dns_token' => $office->domain_dns_token,
            'dns_instructions' => $this->dnsInstructions($office),
            'platform_dns' => $this->platformDnsRecords(),
            'latest_order' => $order ? [
                'id' => $order->id,
                'domain_name' => $order->domain_name,
                'status' => $order->status,
                'price' => $order->price,
                'is_available' => $order->is_available,
                'created_at' => $order->created_at?->toIso8601String(),
            ] : null,
            'ir_domain_price' => self::IR_DOMAIN_PRICE,
        ];
    }

    /** @return array{available: bool|null, message: string, domain_name: string} */
    public function checkAvailability(string $domainName): array
    {
        $domainName = $this->normalizeDomain($domainName);
        $result = $this->whois->checkAvailability($domainName);

        return [
            'domain_name' => $domainName,
            'available' => $result['available'],
            'message' => $result['message'],
        ];
    }

    public function initiatePayment(User $user, string $domainName): array
    {
        $office = $user->office;
        if (! $office) {
            throw ValidationException::withMessages(['domain' => ['دفتر یافت نشد.']]);
        }

        if (! $user->canManageOffice()) {
            throw ValidationException::withMessages(['domain' => ['فقط مدیر دفتر می‌تواند دامنه سفارش دهد.']]);
        }

        $domainName = $this->normalizeDomain($domainName);
        $check = $this->whois->checkAvailability($domainName);

        if ($check['available'] === false) {
            throw ValidationException::withMessages([
                'domain_name' => [$check['message']],
            ]);
        }

        $pending = DomainOrder::where('office_id', $office->id)
            ->whereIn('status', ['pending_payment', 'paid', 'purchasing', 'purchased', 'dns_pending'])
            ->exists();

        if ($pending) {
            throw ValidationException::withMessages([
                'domain_name' => ['یک سفارش دامنه در حال پردازش دارید.'],
            ]);
        }

        $order = DomainOrder::create([
            'office_id' => $office->id,
            'requested_by' => $user->id,
            'domain_name' => $domainName,
            'status' => 'pending_payment',
            'price' => self::IR_DOMAIN_PRICE,
            'is_available' => $check['available'],
            'availability_note' => $check['message'],
        ]);

        $payment = Payment::create([
            'office_id' => $office->id,
            'gateway' => PaymentGateway::Zibal,
            'status' => 'pending',
            'amount' => self::IR_DOMAIN_PRICE,
            'authority' => 'pending',
            'metadata' => [
                'type' => 'domain_order',
                'domain_order_id' => $order->id,
                'domain_name' => $domainName,
            ],
        ]);

        $order->update(['payment_id' => $payment->id]);

        $appUrl = rtrim(config('app.url'), '/');
        $callbackUrl = $appUrl.'/api/v1/payments/zibal/callback';

        $result = $this->zibal->request(
            self::IR_DOMAIN_PRICE,
            "خرید دامنه {$domainName} — پوشه",
            $callbackUrl,
            $payment->id,
            $user->mobile,
        );

        $payment->update(['authority' => $result['track_id']]);

        return [
            'order_id' => $order->id,
            'payment_id' => $payment->id,
            'amount' => self::IR_DOMAIN_PRICE,
            'redirect_url' => $result['redirect_url'],
            'domain_name' => $domainName,
            'availability' => $check['message'],
        ];
    }

    public function markOrderPaid(Payment $payment): DomainOrder
    {
        $orderId = $payment->metadata['domain_order_id'] ?? null;
        $order = DomainOrder::findOrFail($orderId);

        $order->update(['status' => 'paid']);

        return $order->fresh();
    }

    public function connectDomain(User $user, string $domainName): Office
    {
        $office = $user->office;
        if (! $office || ! $user->canManageOffice()) {
            throw ValidationException::withMessages(['domain' => ['دسترسی ندارید.']]);
        }

        $domainName = $this->normalizeDomain($domainName);
        $office->update([
            'custom_domain' => $domainName,
            'custom_domain_status' => 'dns_pending',
            'domain_dns_token' => Str::random(32),
        ]);

        return $office->fresh();
    }

    public function assignDomainByAdmin(DomainOrder $order, array $data): Office
    {
        $office = $order->office;
        if (! $office) {
            throw ValidationException::withMessages(['office' => ['دفتر یافت نشد.']]);
        }

        $domainName = strtolower(trim($data['domain_name'] ?? $order->domain_name));
        $token = Str::random(32);

        $office->update([
            'custom_domain' => $domainName,
            'custom_domain_status' => $data['status'] ?? 'dns_pending',
            'domain_dns_token' => $token,
        ]);

        $order->update([
            'domain_name' => $domainName,
            'status' => $data['order_status'] ?? 'purchased',
            'admin_notes' => $data['admin_notes'] ?? $order->admin_notes,
            'purchased_at' => now(),
        ]);

        return $office->fresh();
    }

    public function verifyDns(Office $office): bool
    {
        if (! $office->custom_domain || ! $office->domain_dns_token) {
            return false;
        }

        $office->update(['custom_domain_status' => 'verified']);

        DomainOrder::where('office_id', $office->id)
            ->where('domain_name', $office->custom_domain)
            ->whereIn('status', ['purchased', 'dns_pending', 'paid'])
            ->update(['status' => 'connected', 'connected_at' => now()]);

        return true;
    }

    public function activateDomain(Office $office): void
    {
        $office->update(['custom_domain_status' => 'active']);
    }

    /** @return array<int, array<string, string>> */
    public function dnsInstructions(Office $office): array
    {
        if (! $office->custom_domain || ! $office->domain_dns_token) {
            return [];
        }

        $records = $this->platformDnsRecords();

        return array_merge($records, [
            ['type' => 'TXT', 'host' => '_posheh-verify', 'value' => $office->domain_dns_token, 'note' => 'تأیید مالکیت در پوشه'],
        ]);
    }

    /** DNS records admin sets on nic.ir — shown to platform admin */
    public function platformDnsRecords(): array
    {
        $serverIp = config('app.office_site_server_ip', '191.101.113.33');
        $cnameTarget = config('app.office_site_cname', 'sites.posheapp.ir');

        return [
            ['type' => 'A', 'host' => '@', 'value' => $serverIp, 'note' => 'اشاره دامنه به سرور پوشه'],
            ['type' => 'CNAME', 'host' => 'www', 'value' => $cnameTarget, 'note' => 'اختیاری — زیردامنه www'],
        ];
    }

    private function normalizeDomain(string $domainName): string
    {
        $domainName = strtolower(trim($domainName));
        if (! str_ends_with($domainName, '.ir')) {
            $domainName .= '.ir';
        }

        if (! preg_match('/^[a-z0-9]([a-z0-9-]*[a-z0-9])?\.ir$/', $domainName)) {
            throw ValidationException::withMessages([
                'domain_name' => ['دامنه باید با .ir تمام شود (مثال: myoffice.ir)'],
            ]);
        }

        return $domainName;
    }
}
