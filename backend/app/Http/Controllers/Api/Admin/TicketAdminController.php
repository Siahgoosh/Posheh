<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\Ticket\TicketService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TicketAdminController extends Controller
{
    public function __construct(private readonly TicketService $tickets) {}

    public function index(): JsonResponse
    {
        return response()->json($this->tickets->listAll());
    }

    public function reply(Request $request, int $id): JsonResponse
    {
        $data = $request->validate(['message' => ['required', 'string']]);
        $reply = $this->tickets->reply($request->user(), $id, $data['message'], isStaff: true);

        return response()->json(['data' => $reply]);
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $data = $request->validate(['status' => ['required', 'in:open,in_progress,closed']]);
        $ticket = $this->tickets->updateStatus($request->user(), $id, $data['status']);

        return response()->json(['data' => $ticket]);
    }
}
