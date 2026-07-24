<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Services\Admin\AuditLogService;
use App\Services\Subscription\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminPaymentController extends Controller
{
    public function __construct(
        private readonly AuditLogService $audit,
        private readonly SubscriptionService $subscriptions,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = Payment::with(['office:id,name,slug'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('gateway'), fn ($q) => $q->where('gateway', $request->string('gateway')))
            ->when($request->filled('office_id'), fn ($q) => $q->where('office_id', $request->integer('office_id')))
            ->latest('paid_at')
            ->latest();

        $stats = [
            'total_revenue' => (int) Payment::where('status', 'paid')->sum('amount'),
            'monthly_revenue' => (int) Payment::where('status', 'paid')->whereMonth('paid_at', now()->month)->sum('amount'),
            'paid_count' => Payment::where('status', 'paid')->count(),
            'failed_count' => Payment::where('status', 'failed')->count(),
            'pending_count' => Payment::where('status', 'pending')->count(),
        ];

        return response()->json(array_merge($stats, $query->paginate(20)->toArray()));
    }

    public function show(int $id): JsonResponse
    {
        $payment = Payment::with('office')->findOrFail($id);

        return response()->json(['data' => $payment]);
    }
}
