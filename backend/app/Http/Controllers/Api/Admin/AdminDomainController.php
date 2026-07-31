<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\DomainOrder;
use App\Services\Admin\AuditLogService;
use App\Services\Office\DomainService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminDomainController extends Controller
{
    public function __construct(
        private readonly AuditLogService $audit,
        private readonly DomainService $domainService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = DomainOrder::with(['office:id,name,slug', 'requester:id,name,email'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->latest();

        return response()->json($query->paginate(20));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $order = DomainOrder::with('office')->findOrFail($id);

        $data = $request->validate([
            'status' => ['required', 'in:pending,paid,purchasing,purchased,rejected,connected'],
            'admin_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $order->update([
            'status' => $data['status'],
            'admin_notes' => $data['admin_notes'] ?? $order->admin_notes,
            'purchased_at' => in_array($data['status'], ['purchased', 'connected']) ? now() : $order->purchased_at,
        ]);

        if ($data['status'] === 'purchased' && $order->office) {
            $order->office->update([
                'custom_domain' => $order->domain_name,
                'custom_domain_status' => 'dns_pending',
                'domain_dns_token' => $order->office->domain_dns_token ?? \Illuminate\Support\Str::random(32),
            ]);
        }

        if ($data['status'] === 'connected' && $order->office) {
            $this->domainService->activateDomain($order->office);
            $order->update(['connected_at' => now()]);
        }

        $this->audit->log('domain_order.updated', DomainOrder::class, $order->id, 'به‌روزرسانی سفارش دامنه', null, $data);

        return response()->json(['data' => $order->fresh(['office', 'requester'])]);
    }
}
