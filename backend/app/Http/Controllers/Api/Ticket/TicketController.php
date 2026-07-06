<?php

namespace App\Http\Controllers\Api\Ticket;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Ticket::with(['office:id,name', 'user:id,name'])
            ->latest();

        if (! $request->user()->isSuperAdmin()) {
            $query->where('office_id', $request->user()->office_id);
        }

        return response()->json($query->paginate(20));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
            'priority' => ['nullable', 'in:low,medium,high'],
        ]);

        $ticket = Ticket::create([
            ...$data,
            'office_id' => $request->user()->office_id,
            'user_id' => $request->user()->id,
            'priority' => $data['priority'] ?? 'medium',
            'status' => 'open',
        ]);

        return response()->json(['data' => $ticket->load(['office:id,name', 'user:id,name'])], 201);
    }
}
