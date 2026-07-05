<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Office;
use App\Models\Payment;
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
        ]);
    }

    public function tickets(Request $request): JsonResponse
    {
        $tickets = Ticket::with(['user', 'office'])
            ->latest()
            ->paginate(20);

        return response()->json($tickets);
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
        ]);

        $office = Office::findOrFail($id);
        $office->update($request->only(['is_active', 'name']));

        return response()->json([
            'data' => $office->load('subscription.plan'),
            'message' => 'دفتر به‌روزرسانی شد.',
        ]);
    }
}
