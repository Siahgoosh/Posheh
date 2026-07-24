<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Office;
use App\Models\Payment;
use App\Models\User;
use App\Services\Admin\AuditLogService;
use App\Services\Admin\PlatformDataService;
use App\Services\Settings\SystemSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminOperationsController extends Controller
{
    public function __construct(
        private readonly PlatformDataService $platform,
        private readonly SystemSettingsService $settings,
        private readonly AuditLogService $audit,
    ) {}

    public function overview(): JsonResponse
    {
        return response()->json(['data' => $this->platform->overview()]);
    }

    public function revenue(): JsonResponse
    {
        return response()->json(['data' => $this->platform->revenueDetail()]);
    }

    public function churn(): JsonResponse
    {
        return response()->json(['data' => $this->platform->churnRiskOffices(20)]);
    }

    public function maintenance(): JsonResponse
    {
        return response()->json([
            'data' => [
                'maintenance_mode' => (bool) $this->settings->get('maintenance_mode', false),
                'maintenance_message' => (string) $this->settings->get('maintenance_message', 'سیستم در حال به‌روزرسانی است.'),
                'registration_enabled' => (bool) $this->settings->get('registration_enabled', true),
                'trial_enabled' => (bool) $this->settings->get('trial_enabled', true),
            ],
        ]);
    }

    public function updateMaintenance(Request $request): JsonResponse
    {
        $data = $request->validate([
            'maintenance_mode' => ['sometimes', 'boolean'],
            'maintenance_message' => ['sometimes', 'string', 'max:500'],
            'registration_enabled' => ['sometimes', 'boolean'],
            'trial_enabled' => ['sometimes', 'boolean'],
        ]);

        foreach ($data as $key => $value) {
            $this->settings->set($key, $value);
        }

        $this->audit->log('platform.maintenance_updated', null, null, 'تغییر تنظیمات پلتفرم', null, $data);

        return response()->json(['message' => 'ذخیره شد.', 'data' => $data]);
    }

    public function export(Request $request, string $type): StreamedResponse|JsonResponse
    {
        $allowed = ['users', 'offices', 'payments', 'customers'];
        if (! in_array($type, $allowed, true)) {
            return response()->json(['message' => 'نوع export نامعتبر است.'], 422);
        }

        $filename = "posheh-{$type}-".now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($type) {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));

            match ($type) {
                'users' => $this->exportUsers($out),
                'offices' => $this->exportOffices($out),
                'payments' => $this->exportPayments($out),
                'customers' => $this->exportCustomers($out),
            };

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** @param resource $out */
    private function exportUsers($out): void
    {
        fputcsv($out, ['id', 'name', 'mobile', 'email', 'role', 'office', 'is_active', 'created_at']);
        User::with('office:id,name')->orderBy('id')->chunk(200, function ($rows) use ($out) {
            foreach ($rows as $u) {
                fputcsv($out, [
                    $u->id, $u->name, $u->mobile, $u->email,
                    $u->role instanceof \BackedEnum ? $u->role->value : $u->role,
                    $u->office?->name, $u->is_active ? '1' : '0', $u->created_at,
                ]);
            }
        });
    }

    /** @param resource $out */
    private function exportOffices($out): void
    {
        fputcsv($out, ['id', 'name', 'slug', 'city', 'is_active', 'plan_active', 'trial_ends_at', 'created_at']);
        Office::orderBy('id')->chunk(200, function ($rows) use ($out) {
            foreach ($rows as $o) {
                fputcsv($out, [
                    $o->id, $o->name, $o->slug, $o->city, $o->is_active ? '1' : '0',
                    $o->plan_active ? '1' : '0', $o->trial_ends_at, $o->created_at,
                ]);
            }
        });
    }

    /** @param resource $out */
    private function exportPayments($out): void
    {
        fputcsv($out, ['id', 'office', 'amount', 'status', 'gateway', 'ref_id', 'paid_at']);
        Payment::with('office:id,name')->orderByDesc('id')->chunk(200, function ($rows) use ($out) {
            foreach ($rows as $p) {
                fputcsv($out, [
                    $p->id, $p->office?->name, $p->amount, $p->status, $p->gateway, $p->ref_id, $p->paid_at,
                ]);
            }
        });
    }

    /** @param resource $out */
    private function exportCustomers($out): void
    {
        fputcsv($out, ['id', 'office', 'name', 'mobile', 'priority', 'preferred_city', 'created_at']);
        Customer::with('office:id,name')->orderBy('id')->chunk(200, function ($rows) use ($out) {
            foreach ($rows as $c) {
                fputcsv($out, [
                    $c->id, $c->office?->name, $c->name, $c->mobile, $c->priority, $c->preferred_city, $c->created_at,
                ]);
            }
        });
    }
}
