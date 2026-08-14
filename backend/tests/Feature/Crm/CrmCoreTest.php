<?php

namespace Tests\Feature\Crm;

use App\Enums\UserRole;
use App\Models\CrmDeal;
use App\Models\CrmPipelineStage;
use App\Models\Customer;
use App\Models\Office;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\Wallet;
use App\Services\Crm\CrmBootstrapService;
use App\Services\Crm\CrmService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CrmCoreTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;
    private User $consultant;
    private Office $office;

    protected function setUp(): void
    {
        parent::setUp();

        $this->office = Office::create(['name' => 'CRM Office', 'slug' => 'crm-office', 'is_active' => true]);
        Wallet::create(['office_id' => $this->office->id, 'balance' => 0]);

        $plan = SubscriptionPlan::create([
            'slug' => 'crm-plan',
            'name' => 'CRM',
            'max_users' => 5,
            'max_properties' => 100,
            'storage_gb' => 5,
            'monthly_price' => 990000,
            'features' => ['crm', 'lead_scoring', 'commissions'],
        ]);

        Subscription::create([
            'office_id' => $this->office->id,
            'subscription_plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
        ]);

        $this->manager = User::create([
            'name' => 'Manager',
            'mobile' => '09120000001',
            'office_id' => $this->office->id,
            'role' => UserRole::OfficeManager,
            'is_active' => true,
        ]);

        $this->consultant = User::create([
            'name' => 'Consultant',
            'mobile' => '09120000002',
            'office_id' => $this->office->id,
            'role' => UserRole::Consultant,
            'is_active' => true,
        ]);

        app(CrmBootstrapService::class)->ensureDefaultsForOffice($this->office->id);
    }

    public function test_bootstrap_creates_default_stages(): void
    {
        $this->assertGreaterThanOrEqual(6, CrmPipelineStage::where('office_id', $this->office->id)->count());
    }

    public function test_create_deal_and_tenant_isolation(): void
    {
        Sanctum::actingAs($this->manager);
        $crm = app(CrmService::class);
        $deal = $crm->create($this->manager, [
            'title' => 'خرید آپارتمان',
            'contact_name' => 'علی',
            'contact_mobile' => '09121234567',
            'value' => 10_000_000_000,
            'source' => 'divar',
        ]);

        $this->assertSame($this->office->id, $deal->office_id);
        $this->assertSame('lead', $deal->stage);
        $this->assertGreaterThan(30, (int) $deal->lead_score);

        $other = Office::create(['name' => 'Other', 'slug' => 'crm-other', 'is_active' => true]);
        Wallet::create(['office_id' => $other->id, 'balance' => 0]);
        $otherUser = User::create([
            'name' => 'Other',
            'mobile' => '09120000003',
            'office_id' => $other->id,
            'role' => UserRole::OfficeManager,
            'is_active' => true,
        ]);

        Sanctum::actingAs($otherUser);
        $list = app(CrmService::class)->list($otherUser);
        $this->assertCount(0, $list);
    }

    public function test_consultant_only_sees_assigned_deals(): void
    {
        $crm = app(CrmService::class);
        $crm->create($this->manager, [
            'title' => 'معامله مدیر',
            'assigned_to' => $this->manager->id,
            'contact_mobile' => '09121111111',
        ]);
        $crm->create($this->manager, [
            'title' => 'معامله مشاور',
            'assigned_to' => $this->consultant->id,
            'contact_mobile' => '09122222222',
        ]);

        $list = $crm->list($this->consultant);
        $this->assertCount(1, $list);
        $this->assertSame('معامله مشاور', $list->first()->title);
    }

    public function test_lost_requires_reason(): void
    {
        $this->expectException(ValidationException::class);
        $crm = app(CrmService::class);
        $deal = $crm->create($this->manager, ['title' => 'Lost test', 'contact_mobile' => '09123333333']);
        $crm->update($this->manager, $deal->id, ['stage' => 'closed_lost']);
    }

    public function test_duplicate_mobile_detection(): void
    {
        Customer::create([
            'office_id' => $this->office->id,
            'created_by' => $this->manager->id,
            'name' => 'مشتری قبلی',
            'mobile' => '09125556666',
        ]);

        $hits = app(CrmService::class)->findDuplicateCustomers($this->manager, '09125556666');
        $this->assertCount(1, $hits);
        $this->assertSame('مشتری قبلی', $hits[0]['name']);
    }

    public function test_api_stages_endpoint(): void
    {
        Sanctum::actingAs($this->manager);
        $this->getJson('/api/v1/crm/stages')->assertOk()->assertJsonStructure(['data']);
    }
}
