<?php

namespace App\Http\Controllers\Api\Communication;

use App\Http\Controllers\Controller;
use App\Models\Communication\CommWebhookLog;
use App\Modules\Communication\Application\Services\TelegramPlatformHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommunicationTelegramWebhookController extends Controller
{
    public function __construct(private readonly TelegramPlatformHandler $handler) {}

    public function handle(Request $request): JsonResponse
    {
        $payload = $request->all();

        CommWebhookLog::create([
            'provider' => 'telegram',
            'event' => 'webhook',
            'payload' => $payload,
            'ok' => true,
        ]);

        try {
            $this->handler->handle($payload);
        } catch (\Throwable $e) {
            CommWebhookLog::create([
                'provider' => 'telegram',
                'event' => 'webhook_error',
                'payload' => ['error' => $e->getMessage()],
                'ok' => false,
            ]);
        }

        return response()->json(['ok' => true]);
    }
}
