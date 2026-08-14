<?php

namespace App\Modules\Communication\Infrastructure\Telegram;

use App\Models\Communication\CommTelegramLog;
use App\Modules\Communication\Application\Services\CommunicationSettingsService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramApiClient
{
    public function __construct(private readonly CommunicationSettingsService $commSettings) {}

    public function token(): string
    {
        return $this->commSettings->telegramBotToken();
    }

    public function isConfigured(): bool
    {
        return $this->token() !== '';
    }

    /** @return array<string, mixed> */
    public function call(string $method, array $params = []): array
    {
        $token = $this->token();
        if ($token === '') {
            return ['ok' => false, 'description' => 'Telegram bot token not configured'];
        }

        try {
            $response = Http::timeout(30)->post("https://api.telegram.org/bot{$token}/{$method}", $params);
            $body = $response->json() ?? [];

            CommTelegramLog::create([
                'direction' => 'outbound',
                'method' => $method,
                'payload' => $params,
                'response' => $body,
                'ok' => (bool) ($body['ok'] ?? false),
            ]);

            return $body;
        } catch (\Throwable $e) {
            Log::error('Telegram API error', ['method' => $method, 'error' => $e->getMessage()]);
            CommTelegramLog::create([
                'direction' => 'outbound',
                'method' => $method,
                'payload' => $params,
                'response' => ['error' => $e->getMessage()],
                'ok' => false,
            ]);

            return ['ok' => false, 'description' => $e->getMessage()];
        }
    }

    public function sendMessage(int|string $chatId, string $text, array $extra = []): array
    {
        return $this->call('sendMessage', array_merge([
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'HTML',
        ], $extra));
    }

    public function sendPhoto(int|string $chatId, string $filePath, ?string $caption = null): array
    {
        return $this->uploadFile('sendPhoto', $chatId, 'photo', $filePath, $caption);
    }

    public function sendDocument(int|string $chatId, string $filePath, ?string $caption = null): array
    {
        return $this->uploadFile('sendDocument', $chatId, 'document', $filePath, $caption);
    }

    public function sendVoice(int|string $chatId, string $filePath): array
    {
        return $this->uploadFile('sendVoice', $chatId, 'voice', $filePath);
    }

    public function sendVideo(int|string $chatId, string $filePath, ?string $caption = null): array
    {
        return $this->uploadFile('sendVideo', $chatId, 'video', $filePath, $caption);
    }

    public function getFile(string $fileId): array
    {
        return $this->call('getFile', ['file_id' => $fileId]);
    }

    public function registerCommunicationWebhook(): array
    {
        $baseUrl = rtrim((string) config('app.url'), '/');
        $url = $baseUrl.'/api/v1/communication/telegram/webhook';

        return $this->call('setWebhook', [
            'url' => $url,
            'allowed_updates' => ['message', 'edited_message'],
        ]);
    }

    public function downloadFile(string $filePath): ?string
    {
        $token = $this->token();
        $url = "https://api.telegram.org/file/bot{$token}/{$filePath}";

        try {
            $response = Http::timeout(60)->get($url);
            if (! $response->successful()) {
                return null;
            }

            $local = storage_path('app/comm/inbound/'.basename($filePath));
            if (! is_dir(dirname($local))) {
                mkdir(dirname($local), 0755, true);
            }
            file_put_contents($local, $response->body());

            return $local;
        } catch (\Throwable $e) {
            Log::error('Telegram file download failed', ['error' => $e->getMessage()]);

            return null;
        }
    }

    private function uploadFile(string $method, int|string $chatId, string $field, string $filePath, ?string $caption = null): array
    {
        $token = $this->token();
        if (! is_file($filePath)) {
            return ['ok' => false, 'description' => 'File not found'];
        }

        try {
            $request = Http::timeout(60)->attach($field, fopen($filePath, 'r'), basename($filePath));
            $params = ['chat_id' => $chatId];
            if ($caption) {
                $params['caption'] = $caption;
            }
            $response = $request->post("https://api.telegram.org/bot{$token}/{$method}", $params);
            $body = $response->json() ?? [];

            CommTelegramLog::create([
                'direction' => 'outbound',
                'method' => $method,
                'payload' => ['chat_id' => $chatId, 'file' => basename($filePath)],
                'response' => $body,
                'ok' => (bool) ($body['ok'] ?? false),
            ]);

            return $body;
        } catch (\Throwable $e) {
            return ['ok' => false, 'description' => $e->getMessage()];
        }
    }
}
