<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Office;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function offices(Request $request): JsonResponse
    {
        $offices = Office::with(['subscription.plan', 'users'])
            ->withCount('properties')
            ->latest()
            ->paginate(20);

        return response()->json($offices);
    }

    public function analytics(): JsonResponse
    {
        return response()->json([
            'total_offices' => Office::count(),
            'active_offices' => Office::where('is_active', true)->count(),
            'total_users' => User::count(),
            'total_revenue' => Payment::where('status', 'paid')->sum('amount'),
            'monthly_revenue' => Payment::where('status', 'paid')
                ->whereMonth('paid_at', now()->month)
                ->sum('amount'),
            'open_tickets' => Ticket::where('status', 'open')->count(),
        ]);
    }

    public function payments(Request $request): JsonResponse
    {
        $payments = Payment::with(['office:id,name', 'subscription.plan:id,name'])
            ->latest('paid_at')
            ->latest()
            ->paginate(30);

        return response()->json($payments);
    }

    public function tickets(Request $request): JsonResponse
    {
        $tickets = Ticket::with(['user:id,name,mobile', 'office:id,name'])
            ->latest()
            ->paginate(20);

        return response()->json($tickets);
    }

    public function updateTicket(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'status' => ['sometimes', 'in:open,in_progress,resolved,closed'],
            'priority' => ['sometimes', 'in:low,medium,high'],
            'assigned_to' => ['nullable', 'exists:users,id'],
        ]);

        $ticket = Ticket::findOrFail($id);
        $ticket->update($request->only(['status', 'priority', 'assigned_to']));

        return response()->json([
            'data' => $ticket->load(['user:id,name', 'office:id,name']),
            'message' => 'تیکت به‌روزرسانی شد.',
        ]);
    }

    public function announcements(): JsonResponse
    {
        return response()->json([
            'data' => Announcement::where('is_active', true)->latest()->get(),
        ]);
    }

    public function createAnnouncement(Request $request): JsonResponse
    {
        $request->validate([
            'title' => ['required', 'string'],
            'content' => ['required', 'string'],
            'type' => ['nullable', 'string'],
        ]);

        $announcement = Announcement::create($request->all());

        return response()->json(['data' => $announcement], 201);
    }

    public function updateOffice(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'is_active' => ['sometimes', 'boolean'],
            'name' => ['sometimes', 'string', 'max:255'],
            'trial_ends_at' => ['nullable', 'date'],
        ]);

        $office = Office::findOrFail($id);
        $office->update($request->only(['is_active', 'name', 'trial_ends_at']));

        return response()->json([
            'data' => $office->load('subscription.plan'),
            'message' => 'دفتر به‌روزرسانی شد.',
        ]);
    }

    public function extendSubscription(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'days' => ['required', 'integer', 'min:1', 'max:365'],
        ]);

        $office = Office::findOrFail($id);
        $subscription = $office->subscription;

        if ($subscription) {
            $base = $subscription->ends_at && $subscription->ends_at->isFuture()
                ? $subscription->ends_at->copy()
                : now();
            $subscription->update([
                'status' => 'active',
                'ends_at' => $base->addDays($request->integer('days')),
            ]);
        } else {
            $trialBase = $office->trial_ends_at && $office->trial_ends_at->isFuture()
                ? $office->trial_ends_at->copy()
                : now();
            $office->update([
                'trial_ends_at' => $trialBase->addDays($request->integer('days')),
            ]);
        }

        return response()->json([
            'data' => $office->fresh('subscription.plan'),
            'message' => "اشتراک {$request->integer('days')} روز تمدید شد.",
        ]);
    }
}
