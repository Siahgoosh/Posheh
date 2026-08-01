<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\Ticket\TicketService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TicketAdminController extends Controller
{
    public function __construct(private readonly TicketService $tickets) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json($this->tickets->listAll(
            $request->string('status')->toString() ?: null,
            $request->string('priority')->toString() ?: null,
        ));
    }

    public function show(int $id): JsonResponse
    {
        return response()->json(['data' => $this->tickets->getForAdmin($id)]);
    }

    public function reply(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'message' => ['required', 'string'],
            'is_internal' => ['sometimes', 'boolean'],
        ]);

        $reply = $this->tickets->reply(
            $request->user(),
            $id,
            $data['message'],
            isStaff: true,
            isInternal: $request->boolean('is_internal'),
        );

        return response()->json(['data' => $reply]);
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $data = $request->validate(['status' => ['required', 'in:open,in_progress,closed']]);
        $ticket = $this->tickets->updateStatus($request->user(), $id, $data['status']);

        return response()->json(['data' => $ticket]);
    }

    public function assign(Request $request, int $id): JsonResponse
    {
        $data = $request->validate(['assigned_to' => ['nullable', 'integer']]);
        $ticket = $this->tickets->assign($request->user(), $id, $data['assigned_to'] ?? null);

        return response()->json(['data' => $ticket]);
    }
}
