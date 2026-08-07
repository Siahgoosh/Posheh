<?php

namespace Tests\Feature\Communication;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CommunicationTelegramWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_telegram_webhook_creates_visitor_message(): void
    {
        config(['communication.telegram.platform_bot_token' => 'test-token']);
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 99]]),
        ]);

        $update = [
            'update_id' => 1001,
            'message' => [
                'message_id' => 55,
                'chat' => ['id' => 123456],
                'from' => ['id' => 789, 'first_name' => 'کاربر', 'username' => 'user1'],
                'text' => 'سلام، می‌خوام دمو بگیرم',
            ],
        ];

        $response = $this->postJson('/api/v1/communication/telegram/webhook', $update);

        $response->assertOk();
        $this->assertDatabaseHas('comm_messages', ['body' => 'سلام، می‌خوام دمو بگیرم']);
        $this->assertDatabaseHas('comm_conversations', ['channel' => 'telegram', 'external_chat_id' => '123456']);
        $this->assertDatabaseHas('comm_telegram_updates', ['update_id' => 1001]);
    }

    public function test_telegram_webhook_ignores_duplicate_updates(): void
    {
        config(['communication.telegram.platform_bot_token' => 'test-token']);
        Http::fake();

        $update = [
            'update_id' => 2002,
            'message' => [
                'message_id' => 56,
                'chat' => ['id' => 999],
                'from' => ['id' => 888, 'first_name' => 'تست'],
                'text' => 'پیام اول',
            ],
        ];

        $this->postJson('/api/v1/communication/telegram/webhook', $update)->assertOk();
        $this->postJson('/api/v1/communication/telegram/webhook', $update)->assertOk();

        $this->assertEquals(1, \App\Models\Communication\CommMessage::where('body', 'پیام اول')->count());
    }
}
