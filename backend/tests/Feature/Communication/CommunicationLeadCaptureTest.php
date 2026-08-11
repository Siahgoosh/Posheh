<?php

namespace Tests\Feature\Communication;

use Database\Seeders\CommunicationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommunicationLeadCaptureTest extends TestCase
{
    use RefreshDatabase;

    public function test_visitor_init_and_lead_capture_flow(): void
    {
        $this->seed(CommunicationSeeder::class);

        $init = $this->postJson('/api/v1/communication/visitors/init', [
            'session_key' => 'test-session-1',
            'current_page' => '/pricing',
            'language' => 'fa-IR',
        ]);

        $init->assertOk();
        $token = $init->json('data.visitor_token');
        $this->assertNotEmpty($token);

        $lead = $this->postJson('/api/v1/communication/leads', [
            'visitor_token' => $token,
            'session_key' => 'test-session-1',
            'first_name' => 'علی',
            'last_name' => 'تست',
            'mobile' => '09121234567',
            'office_name' => 'دفتر نمونه',
            'request_type' => 'demo',
            'staff_count' => 8,
            'budget' => '۵۰ میلیون',
        ]);

        $lead->assertCreated();
        $this->assertDatabaseHas('comm_leads', [
            'mobile' => '09121234567',
            'office_name' => 'دفتر نمونه',
        ]);
        $this->assertNotEmpty($lead->json('data.conversation_uuid'));
    }
}
