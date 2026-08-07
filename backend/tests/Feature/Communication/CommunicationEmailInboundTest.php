<?php

namespace Tests\Feature\Communication;

use App\Models\Communication\CommConversation;
use App\Models\Communication\CommEmailThread;
use App\Models\Communication\CommTicket;
use App\Models\Communication\CommVisitor;
use Database\Seeders\CommunicationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CommunicationEmailInboundTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_inbound_adds_message_to_conversation(): void
    {
        $this->seed(CommunicationSeeder::class);

        $visitor = CommVisitor::create([
            'uuid' => (string) Str::uuid(),
            'first_name' => 'علی',
            'visit_count' => 1,
        ]);

        $conversation = CommConversation::create([
            'uuid' => (string) Str::uuid(),
            'visitor_id' => $visitor->id,
            'channel' => 'website',
            'status' => 'open',
            'subject' => 'تست',
        ]);

        $ticket = CommTicket::create([
            'uuid' => (string) Str::uuid(),
            'conversation_id' => $conversation->id,
            'status' => 'open',
            'subject' => 'تست',
            'email_alias' => 'ticket-abc@test.posheapp.ir',
        ]);

        CommEmailThread::create([
            'conversation_id' => $conversation->id,
            'ticket_id' => $ticket->id,
            'alias_email' => 'ticket-abc@test.posheapp.ir',
            'subject' => 'تست',
        ]);

        $response = $this->postJson('/api/v1/communication/email/inbound', [
            'to_email' => 'ticket-abc@test.posheapp.ir',
            'from_email' => 'customer@example.com',
            'subject' => 'Re: تست',
            'body_text' => 'پاسخ از ایمیل',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('comm_messages', ['body' => 'پاسخ از ایمیل']);
        $this->assertDatabaseHas('comm_email_messages', ['direction' => 'inbound', 'from_email' => 'customer@example.com']);
    }
}
