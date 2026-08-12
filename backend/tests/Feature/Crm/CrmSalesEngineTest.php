<?php

namespace Tests\Feature\Crm;

use App\Enums\PropertyStatus;
use App\Enums\PropertyType;
use App\Enums\UserRole;
use App\Models\CrmAutomationLog;
use App\Models\CrmDeal;
use App\Models\CrmNeedProfile;
use App\Models\CrmNegotiation;
use App\Models\CrmOffer;
use App\Models\Customer;
use App\Models\Office;
use App\Models\Property;
use App\Models\PropertyVisit;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\Wallet;
use App\Services\Crm\CrmAutomationService;
use App\Services\Crm\CrmBootstrapService;
use App\Services\Crm\CrmService;
use App\Services\Crm\OfferNegotiationService;
use App\Services\Crm\PropertyMatchingService;
use App\Services\Visit\VisitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CrmSalesEngineTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;
    private Office $office;

    protected function setUp(): void
    {
        parent::setUp();

        $this->office = Office::create(['name' => 'Sales Office', 'slug' => 'sales-office', 'is_active' => true]);
        Wallet::create(['office_id' => $this->office->id, 'balance' => 0]);

        $plan = SubscriptionPlan::create([
            'slug' => 'sales-plan',
            'name' => 'Sales',
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
            'name' => 'Sales Manager',
            'mobile' => '09121110001',
            'office_id' => $this->office->id,
            'role' => UserRole::OfficeManager,
            'is_active' => true,
        ]);

        app(CrmBootstrapService::class)->ensureDefaultsForOffice($this->office->id);
    }

    private function makeCustomer(array $extra = []): Customer
    {
        return Customer::create(array_merge([
            'office_id' => $this->office->id,
            'created_by' => $this->manager->id,
            'name' => 'خریدار تست',
            'mobile' => '09123334455',
            'budget_min' => 3_000_000_000,
            'budget_max' => 5_000_000_000,
            'min_area' => 90,
            'max_area' => 140,
            'min_rooms' => 2,
            'preferred_city' => 'لامرد',
            'preferred_district' => 'سورغال',
            'preferred_type' => 'sale',
        ], $extra));
    }

    private function makeProperty(array $extra = []): Property
    {
        return Property::create(array_merge([
            'office_id' => $this->office->id,
            'created_by' => $this->manager->id,
            'code' => 'P-'.uniqid(),
            'title' => 'آپارتمان تست',
            'type' => PropertyType::Sale,
            'status' => PropertyStatus::Active,
            'price' => 4_500_000_000,
            'area' => 110,
            'rooms' => 2,
            'city' => 'لامرد',
            'district' => 'سورغال',
            'has_parking' => true,
            'has_elevator' => true,
            'building_age' => 5,
        ], $extra));
    }

    public function test_property_matching_explainable_score(): void
    {
        $customer = $this->makeCustomer();
        CrmNeedProfile::create([
            'office_id' => $this->office->id,
            'customer_id' => $customer->id,
            'transaction_type' => 'sale',
            'budget_min' => 3_000_000_000,
            'budget_max' => 5_000_000_000,
            'min_area' => 100,
            'max_area' => 130,
            'bedrooms' => 2,
            'parking_required' => true,
            'elevator_preferred' => true,
            'preferred_locations' => ['سورغال'],
        ]);
        $good = $this->makeProperty();
        $expensive = $this->makeProperty([
            'code' => 'P-EXP',
            'price' => 5_300_000_000,
            'has_parking' => false,
        ]);

        $matcher = app(PropertyMatchingService::class);
        $matches = $matcher->matchForCustomer($this->manager, $customer, 10);

        $this->assertTrue($matches->isNotEmpty());
        $top = $matches->first();
        $this->assertSame($good->id, $top['property']->id);
        $this->assertGreaterThanOrEqual(70, $top['score']);
        $this->assertNotEmpty($top['checks']);
        $this->assertContains($top['match_type'], ['exact', 'strong', 'alternative']);

        $alt = $matches->firstWhere(fn ($m) => $m['property']->id === $expensive->id);
        if ($alt) {
            $this->assertTrue($alt['score'] < $top['score'] || $alt['match_type'] === 'alternative');
        }
    }

    public function test_reverse_matching_summary(): void
    {
        $customer = $this->makeCustomer();
        CrmNeedProfile::create([
            'office_id' => $this->office->id,
            'customer_id' => $customer->id,
            'transaction_type' => 'sale',
            'budget_max' => 5_000_000_000,
            'preferred_locations' => ['سورغال'],
            'parking_required' => true,
        ]);
        $property = $this->makeProperty();

        $result = app(PropertyMatchingService::class)
            ->reverseMatchForProperty($this->manager, $property);

        $this->assertGreaterThanOrEqual(1, $result['total']);
        $this->assertArrayHasKey('hot', $result['summary']);
        $this->assertArrayHasKey('customers', $result);
    }

    public function test_lead_created_automation_logs(): void
    {
        app(CrmAutomationService::class)->ensureDefaultRules($this->office->id);

        $deal = app(CrmService::class)->create($this->manager, [
            'title' => 'Lead اتوماسیون',
            'contact_mobile' => '09120001122',
            'contact_name' => 'تست',
            'value' => 8_000_000_000,
            'source' => 'instagram',
        ]);

        $this->assertDatabaseHas('crm_automation_logs', [
            'office_id' => $this->office->id,
            'trigger' => 'lead_created',
            'subject_id' => $deal->id,
        ]);
        $this->assertTrue(
            CrmAutomationLog::where('office_id', $this->office->id)->where('trigger', 'lead_created')->exists()
        );
    }

    public function test_visit_double_booking_prevented(): void
    {
        $property = $this->makeProperty();
        $at = now()->addDay()->setTime(17, 0);

        $visits = app(VisitService::class);
        $visits->create($this->manager, [
            'property_id' => $property->id,
            'visit_at' => $at->toDateTimeString(),
            'duration_minutes' => 60,
            'assigned_to' => $this->manager->id,
            'status' => 'scheduled',
        ]);

        $this->expectException(ValidationException::class);
        $visits->create($this->manager, [
            'property_id' => $property->id,
            'visit_at' => $at->copy()->addMinutes(30)->toDateTimeString(),
            'duration_minutes' => 60,
            'assigned_to' => $this->manager->id,
            'status' => 'scheduled',
        ]);
    }

    public function test_negotiation_timeline_preserves_offers(): void
    {
        $customer = $this->makeCustomer();
        $property = $this->makeProperty();
        $svc = app(OfferNegotiationService::class);

        $neg = $svc->startNegotiation($this->manager, [
            'property_id' => $property->id,
            'customer_id' => $customer->id,
            'initial_price' => 5_500_000_000,
        ]);

        $svc->addOffer($this->manager, [
            'property_id' => $property->id,
            'negotiation_id' => $neg->id,
            'customer_id' => $customer->id,
            'side' => 'buyer',
            'amount' => 4_900_000_000,
        ]);
        $svc->addOffer($this->manager, [
            'property_id' => $property->id,
            'negotiation_id' => $neg->id,
            'side' => 'seller',
            'amount' => 5_300_000_000,
        ]);
        $svc->addOffer($this->manager, [
            'property_id' => $property->id,
            'negotiation_id' => $neg->id,
            'side' => 'buyer',
            'amount' => 5_100_000_000,
        ]);

        $fresh = CrmNegotiation::with('offers')->find($neg->id);
        $this->assertCount(3, $fresh->offers);
        $this->assertSame(5_100_000_000, (int) $fresh->current_price);
    }

    public function test_offer_accept_and_convert(): void
    {
        Sanctum::actingAs($this->manager);
        $customer = $this->makeCustomer();
        $property = $this->makeProperty();
        $deal = app(CrmService::class)->create($this->manager, [
            'title' => 'Deal convert',
            'customer_id' => $customer->id,
            'property_id' => $property->id,
            'contact_mobile' => $customer->mobile,
        ]);

        $svc = app(OfferNegotiationService::class);
        $neg = $svc->startNegotiation($this->manager, [
            'property_id' => $property->id,
            'customer_id' => $customer->id,
            'crm_deal_id' => $deal->id,
            'initial_price' => 5_000_000_000,
        ]);
        $offer = $svc->addOffer($this->manager, [
            'property_id' => $property->id,
            'negotiation_id' => $neg->id,
            'crm_deal_id' => $deal->id,
            'amount' => 4_800_000_000,
            'side' => 'buyer',
        ]);
        $svc->updateOfferStatus($this->manager, $offer->id, 'accepted');

        $this->postJson("/api/v1/crm/offers/{$offer->id}/convert-deal")
            ->assertOk()
            ->assertJsonPath('data.stage', 'closed_won');
    }

    public function test_sales_queue_and_opportunities_api(): void
    {
        Sanctum::actingAs($this->manager);
        app(CrmService::class)->create($this->manager, [
            'title' => 'Hot lead',
            'contact_mobile' => '09129998877',
            'value' => 12_000_000_000,
            'source' => 'referral',
            'follow_up_at' => now()->subHour()->toDateTimeString(),
            'lead_score' => 90,
        ]);

        $this->getJson('/api/v1/crm/sales-queue')->assertOk()->assertJsonStructure(['data' => ['summary', 'queue']]);
        $this->getJson('/api/v1/crm/opportunities')->assertOk()->assertJsonStructure(['data' => ['hot_leads']]);
        $this->getJson('/api/v1/crm/briefing')->assertOk();
    }

    public function test_matching_weights_configurable(): void
    {
        Sanctum::actingAs($this->manager);
        $this->getJson('/api/v1/crm/matching-weights')->assertOk();
        $this->putJson('/api/v1/crm/matching-weights', [
            'weights' => [
                ['key' => 'location', 'weight' => 30],
                ['key' => 'budget', 'weight' => 20],
            ],
        ])->assertOk();

        $weights = app(PropertyMatchingService::class)->weightsMap($this->office->id);
        $this->assertSame(30, (int) $weights['location']);
    }

    public function test_tenant_isolation_on_negotiations(): void
    {
        $customer = $this->makeCustomer();
        $property = $this->makeProperty();
        $neg = app(OfferNegotiationService::class)->startNegotiation($this->manager, [
            'property_id' => $property->id,
            'customer_id' => $customer->id,
            'initial_price' => 4_000_000_000,
        ]);

        $other = Office::create(['name' => 'Other Sales', 'slug' => 'other-sales', 'is_active' => true]);
        Wallet::create(['office_id' => $other->id, 'balance' => 0]);
        $otherUser = User::create([
            'name' => 'Other',
            'mobile' => '09121110099',
            'office_id' => $other->id,
            'role' => UserRole::OfficeManager,
            'is_active' => true,
        ]);

        Sanctum::actingAs($otherUser);
        $this->getJson("/api/v1/crm/negotiations/{$neg->id}")->assertNotFound();
    }

    public function test_need_profile_api(): void
    {
        Sanctum::actingAs($this->manager);
        $customer = $this->makeCustomer();
        $this->putJson("/api/v1/customers/{$customer->id}/need-profile", [
            'transaction_type' => 'sale',
            'property_type' => 'apartment',
            'budget_max' => 5_000_000_000,
            'parking_required' => true,
            'purchase_timeline' => 'this_month',
            'preferred_locations' => ['سورغال', 'لامرد'],
        ])->assertOk()->assertJsonPath('data.parking_required', true);

        $this->getJson("/api/v1/customers/{$customer->id}/need-profile")
            ->assertOk()
            ->assertJsonPath('data.transaction_type', 'sale');
    }
}
