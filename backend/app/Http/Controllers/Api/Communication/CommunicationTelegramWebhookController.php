<?php

namespace App\Http\Controllers\Api\Communication;

use App\Http\Controllers\Controller;
use App\Models\Communication\CommWebhookLog;
use App\Modules\Communication\Application\Services\TelegramPlatformHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class CommunicationTelegramWebhookController extends Controller
{
    public function __construct(private readonly TelegramPlatformHandler $handler) {}

    /** Browser/health check — Telegram must POST updates here, not GET */
    public function ping(): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'message' => 'Communication Telegram webhook is reachable.',
            'hint' => 'Telegram sends POST requests to this URL. Register via panel Settings or POST /api/v1/admin/communication/telegram/webhook/register',
        ]);
    }

    public function handle(Request $request): JsonResponse
    {
        $payload = $request->all();

        $this->logWebhook('webhook', $payload, true);

        try {
            $this->handler->handle($payload);
        } catch (\Throwable $e) {
            $this->logWebhook('webhook_error', ['error' => $e->getMessage()], false);
        }

        return response()->json(['ok' => true]);
    }

    private function logWebhook(string $event, array $payload, bool $ok): void
    {
        if (! Schema::hasTable('comm_webhook_logs')) {
            return;
        }

        try {
            CommWebhookLog::create([
                'provider' => 'telegram',
                'event' => $event,
                'payload' => $payload,
                'ok' => $ok,
            ]);
        } catch (\Throwable) {
            // Never fail webhook delivery because audit logging failed
        }
    }
}
