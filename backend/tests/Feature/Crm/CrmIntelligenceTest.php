<?php

namespace Tests\Feature\Crm;

use App\Enums\UserRole;
use App\Models\CrmCustomField;
use App\Models\CrmDeal;
use App\Models\CrmNotification;
use App\Models\Customer;
use App\Models\Office;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\Wallet;
use App\Services\Ai\AiService;
use App\Services\Crm\CrmBootstrapService;
use App\Services\Crm\CrmCommunicationService;
use App\Services\Crm\CrmIntelligenceService;
use App\Services\Crm\CrmService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CrmIntelligenceTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;
    private Office $office;

    protected function setUp(): void
    {
        parent::setUp();

        $this->office = Office::create(['name' => 'Intel Office', 'slug' => 'intel-office', 'is_active' => true]);
        Wallet::create(['office_id' => $this->office->id, 'balance' => 0]);

        $plan = SubscriptionPlan::create([
            'slug' => 'intel-plan',
            'name' => 'Intel',
            'max_users' => 5,
            'max_properties' => 100,
            'storage_gb' => 5,
            'monthly_price' => 990000,
            'features' => ['crm', 'lead_scoring', 'commissions', 'advanced_analytics'],
        ]);

        Subscription::create([
            'office_id' => $this->office->id,
            'subscription_plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
        ]);

        $this->manager = User::create([
            'name' => 'Intel Manager',
            'mobile' => '09127770001',
            'office_id' => $this->office->id,
            'role' => UserRole::OfficeManager,
            'is_active' => true,
        ]);

        app(CrmBootstrapService::class)->ensureDefaultsForOffice($this->office->id);
    }

    public function test_executive_dashboard_kpis_and_comparison(): void
    {
        $crm = app(CrmService::class);
        $crm->create($this->manager, [
            'title' => 'Lead A',
            'contact_mobile' => '09121110001',
            'value' => 5_000_000_000,
            'source' => 'instagram',
            'lead_score' => 85,
        ]);
        $won = $crm->create($this->manager, [
            'title' => 'Won Deal',
            'contact_mobile' => '09121110002',
            'value' => 4_000_000_000,
            'source' => 'referral',
        ]);
        $crm->update($this->manager, $won->id, ['stage' => 'closed_won']);

        Sanctum::actingAs($this->manager);
        $this->getJson('/api/v1/crm/executive?period=this_month')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'kpis',
                    'comparison',
                    'funnel',
                    'forecast',
                    'bottlenecks',
                    'sources',
                ],
            ]);

        $data = app(CrmIntelligenceService::class)->executiveDashboard($this->manager, 'this_month');
        $this->assertArrayHasKey('new_leads', $data['kpis']);
        $this->assertArrayHasKey('pipeline_value', $data['forecast']);
        $this->assertNotEmpty($data['funnel']);
    }

    public function test_funnel_and_forecast_apis(): void
    {
        Sanctum::actingAs($this->manager);
        $this->getJson('/api/v1/crm/funnel-analytics')->assertOk();
        $this->getJson('/api/v1/crm/forecast')->assertOk()
            ->assertJsonStructure(['data' => ['pipeline_value', 'weighted_pipeline']]);
        $this->getJson('/api/v1/crm/agents/performance')->assertOk();
        $this->getJson('/api/v1/crm/data-quality')->assertOk()
            ->assertJsonStructure(['data' => ['health_score', 'issues']]);
    }

    public function test_ai_customer_summary_masks_mobile_and_logs_usage(): void
    {
        $customer = Customer::create([
            'office_id' => $this->office->id,
            'created_by' => $this->manager->id,
            'name' => 'مشتری هوش',
            'mobile' => '09121234567',
            'budget_max' => 5_000_000_000,
            'lead_score' => 90,
        ]);

        $summary = app(AiService::class)->customerSummary($this->manager, $customer);
        $this->assertSame('high', $summary['intent']);
        $this->assertStringContainsString('***', (string) $summary['customer']['mobile']);
        $this->assertSame('rule_based', $summary['mode']);
        $this->assertDatabaseHas('ai_usage_logs', [
            'office_id' => $this->office->id,
            'feature' => 'customer_summary',
        ]);
    }

    public function test_ai_message_does_not_invent_facts(): void
    {
        Sanctum::actingAs($this->manager);
        $res = $this->postJson('/api/v1/crm/ai/message', [
            'purpose' => 'intro',
            'customer_name' => 'رضایی',
            'property_title' => 'آپارتمان سورغال',
            'price' => 4_900_000_000,
        ])->assertOk()->json('data');

        $this->assertStringContainsString('رضایی', $res['message']);
        $this->assertStringContainsString('آپارتمان سورغال', $res['message']);
        $this->assertSame('rule_based', $res['mode']);
    }

    public function test_custom_fields_tenant_scoped(): void
    {
        Sanctum::actingAs($this->manager);
        $this->postJson('/api/v1/crm/custom-fields', [
            'entity' => 'customer',
            'key' => 'capital_source',
            'label' => 'منبع سرمایه',
            'type' => 'select',
            'options' => ['شخصی', 'وام', 'شراکت'],
        ])->assertCreated();

        $this->assertSame(1, CrmCustomField::where('office_id', $this->office->id)->count());

        $other = Office::create(['name' => 'Other Intel', 'slug' => 'other-intel', 'is_active' => true]);
        Wallet::create(['office_id' => $other->id, 'balance' => 0]);
        $otherUser = User::create([
            'name' => 'Other',
            'mobile' => '09127770099',
            'office_id' => $other->id,
            'role' => UserRole::OfficeManager,
            'is_active' => true,
        ]);
        Sanctum::actingAs($otherUser);
        $this->getJson('/api/v1/crm/custom-fields')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_notifications_and_onboarding(): void
    {
        $comm = app(CrmCommunicationService::class);
        $comm->ensureOnboarding($this->office->id);
        $n = $comm->notify($this->manager, $this->manager, 'crm', 'تست اعلان', 'بدنه');
        $this->assertInstanceOf(CrmNotification::class, $n);

        Sanctum::actingAs($this->manager);
        $this->getJson('/api/v1/crm/notifications')->assertOk();
        $this->getJson('/api/v1/crm/onboarding')->assertOk();
        $this->getJson('/api/v1/crm/integrations')->assertOk();
    }

    public function test_bulk_assign_requires_manager(): void
    {
        $consultant = User::create([
            'name' => 'Consultant',
            'mobile' => '09127770002',
            'office_id' => $this->office->id,
            'role' => UserRole::Consultant,
            'is_active' => true,
        ]);
        $deal = app(CrmService::class)->create($this->manager, [
            'title' => 'Bulk',
            'assigned_to' => $consultant->id,
            'contact_mobile' => '09123334455',
        ]);

        Sanctum::actingAs($consultant);
        $this->postJson('/api/v1/crm/deals/bulk', [
            'ids' => [$deal->id],
            'action' => 'assign',
            'assigned_to' => $this->manager->id,
        ])->assertOk();

        $this->assertSame($consultant->id, CrmDeal::find($deal->id)->assigned_to);
    }

    public function test_tenant_isolation_executive(): void
    {
        app(CrmService::class)->create($this->manager, [
            'title' => 'Secret',
            'contact_mobile' => '09120000000',
            'value' => 9_000_000_000,
        ]);

        $other = Office::create(['name' => 'X', 'slug' => 'intel-x', 'is_active' => true]);
        Wallet::create(['office_id' => $other->id, 'balance' => 0]);
        $otherUser = User::create([
            'name' => 'X',
            'mobile' => '09127770999',
            'office_id' => $other->id,
            'role' => UserRole::OfficeManager,
            'is_active' => true,
        ]);

        $data = app(CrmIntelligenceService::class)->executiveDashboard($otherUser, 'this_month');
        $this->assertSame(0, $data['kpis']['total_leads'] ?? 0);
    }
}
