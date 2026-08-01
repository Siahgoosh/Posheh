<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Ticket\TicketService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    public function __construct(private readonly TicketService $tickets) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json($this->tickets->listForUser(
            $request->user(),
            $request->string('status')->toString() ?: null,
        ));
    }

    public function show(Request $request, int $id): JsonResponse
    {
        return response()->json(['data' => $this->tickets->getForUser($request->user(), $id)]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string'],
            'priority' => ['nullable', 'in:low,medium,high'],
            'category' => ['nullable', 'string', 'max:50'],
        ]);

        $ticket = $this->tickets->create($request->user(), $data);

        return response()->json(['data' => $ticket, 'message' => 'تیکت ثبت شد.'], 201);
    }

    public function reply(Request $request, int $id): JsonResponse
    {
        $data = $request->validate(['message' => ['required', 'string']]);
        $reply = $this->tickets->reply($request->user(), $id, $data['message']);

        return response()->json(['data' => $reply]);
    }
}
