<?php

namespace App\Http\Controllers\Api\Communication;

use App\Http\Controllers\Controller;
use App\Models\Communication\CommWebhookLog;
use App\Modules\Communication\Application\Services\EmailInboundService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommunicationEmailWebhookController extends Controller
{
    public function __construct(private readonly EmailInboundService $emailInbound) {}

    public function inbound(Request $request): JsonResponse
    {
        $secret = config('communication.email.webhook_secret');
        if ($secret && $request->header('X-Comm-Email-Secret') !== $secret) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $data = $request->validate([
            'to_email' => ['required', 'email'],
            'from_email' => ['required', 'email'],
            'subject' => ['nullable', 'string', 'max:500'],
            'body_text' => ['nullable', 'string'],
            'body_html' => ['nullable', 'string'],
            'external_id' => ['nullable', 'string', 'max:120'],
        ]);

        CommWebhookLog::create([
            'provider' => 'email',
            'event' => 'inbound',
            'payload' => $data,
            'ok' => true,
        ]);

        $message = $this->emailInbound->processInbound($data);

        if (! $message) {
            return response()->json(['message' => 'Thread not found'], 404);
        }

        return response()->json(['ok' => true, 'message_id' => $message->id]);
    }
}
