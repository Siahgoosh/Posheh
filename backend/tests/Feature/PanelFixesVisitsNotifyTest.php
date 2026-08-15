<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\CrmNotification;
use App\Models\Customer;
use App\Models\Office;
use App\Models\OfficeVisitRequest;
use App\Models\Owner;
use App\Models\Property;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\Wallet;
use App\Services\Crm\CrmCommunicationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PanelFixesVisitsNotifyTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;

    private Office $office;

    protected function setUp(): void
    {
        parent::setUp();
        $this->office = Office::create(['name' => 'Fix Office', 'slug' => 'fix-office', 'is_active' => true]);
        Wallet::create(['office_id' => $this->office->id, 'balance' => 0]);
        $plan = SubscriptionPlan::create([
            'slug' => 'fix-plan',
            'name' => 'Fix',
            'max_users' => 5,
            'max_properties' => 100,
            'storage_gb' => 5,
            'monthly_price' => 1,
            'features' => ['crm', 'excel_export'],
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
            'mobile' => '09123334455',
            'office_id' => $this->office->id,
            'role' => UserRole::OfficeManager,
            'is_active' => true,
        ]);
    }

    public function test_notification_mark_seen_and_purge_after_48h(): void
    {
        Sanctum::actingAs($this->manager);
        $comm = app(CrmCommunicationService::class);
        $n = $comm->notify($this->manager, $this->manager, 'system', 'تست', 'بدنه');
        $this->assertNotNull($n);

        $this->postJson('/api/v1/crm/notifications/'.$n->id.'/read')->assertOk();
        $this->assertNotNull($n->fresh()->read_at);

        $old = new CrmNotification([
            'office_id' => $this->office->id,
            'user_id' => $this->manager->id,
            'category' => 'system',
            'title' => 'قدیمی',
        ]);
        $old->created_at = now()->subHours(50);
        $old->updated_at = now()->subHours(50);
        $old->save();

        // Listing purges expired
        $list = $this->getJson('/api/v1/crm/notifications')->assertOk()->json('data');
        $titles = collect($list)->pluck('title');
        $this->assertFalse($titles->contains('قدیمی'));
        $this->assertDatabaseMissing('crm_notifications', ['title' => 'قدیمی']);

        $this->artisan('notifications:purge-old')->assertSuccessful();
    }

    public function test_import_rejects_non_excel(): void
    {
        Sanctum::actingAs($this->manager);
        $csv = tmpfile();
        fwrite($csv, "code,type\nA1,sale\n");
        $path = stream_get_meta_data($csv)['uri'];
        $this->postJson('/api/v1/properties-import', [
            'file' => new \Illuminate\Http\UploadedFile($path, 'x.csv', 'text/csv', null, true),
        ])->assertStatus(422);
    }

    public function test_import_template_downloads(): void
    {
        Sanctum::actingAs($this->manager);
        $this->get('/api/v1/properties-import-template')->assertOk();
    }

    public function test_owner_customer_matches_endpoint(): void
    {
        Sanctum::actingAs($this->manager);
        $owner = Owner::create([
            'office_id' => $this->office->id,
            'name' => 'مالک تست',
            'created_by' => $this->manager->id,
        ]);
        Property::create([
            'office_id' => $this->office->id,
            'created_by' => $this->manager->id,
            'assigned_to' => $this->manager->id,
            'owner_id' => $owner->id,
            'code' => 'OWN-1',
            'type' => 'sale',
            'status' => 'active',
            'price' => 5_000_000_000,
            'city' => 'لامرد',
            'published_at' => now(),
        ]);
        Customer::create([
            'office_id' => $this->office->id,
            'created_by' => $this->manager->id,
            'name' => 'خریدار',
            'mobile' => '09120001122',
            'budget_max' => 6_000_000_000,
            'preferred_city' => 'لامرد',
            'preferred_type' => 'sale',
        ]);

        $this->getJson('/api/v1/owners/'.$owner->id.'/customer-matches')->assertOk()
            ->assertJsonPath('data.owner_id', $owner->id);
    }

    public function test_inbound_visit_convert(): void
    {
        Sanctum::actingAs($this->manager);
        $property = Property::create([
            'office_id' => $this->office->id,
            'created_by' => $this->manager->id,
            'assigned_to' => $this->manager->id,
            'code' => 'V-1',
            'type' => 'sale',
            'status' => 'active',
            'published_at' => now(),
        ]);
        $req = OfficeVisitRequest::create([
            'office_id' => $this->office->id,
            'property_id' => $property->id,
            'name' => 'مهمان وب',
            'mobile' => '09125556677',
            'preferred_date' => now()->addDays(2)->toDateString(),
            'preferred_time' => '16:00',
            'status' => 'new',
        ]);

        $this->getJson('/api/v1/visits/inbound')->assertOk()
            ->assertJsonFragment(['id' => $req->id]);

        $this->postJson('/api/v1/visits/inbound/'.$req->id.'/convert')->assertOk();
        $this->assertSame('converted', $req->fresh()->status);
        $this->assertDatabaseHas('property_visits', [
            'office_id' => $this->office->id,
            'property_id' => $property->id,
        ]);
    }

    public function test_consultant_can_create_visit_with_customer_mobile(): void
    {
        $consultant = User::create([
            'name' => 'Consultant',
            'mobile' => '09126667788',
            'office_id' => $this->office->id,
            'role' => UserRole::Consultant,
            'is_active' => true,
        ]);
        Sanctum::actingAs($consultant);
        $property = Property::create([
            'office_id' => $this->office->id,
            'created_by' => $consultant->id,
            'assigned_to' => $consultant->id,
            'code' => 'C-1',
            'type' => 'rent',
            'status' => 'active',
            'published_at' => now(),
        ]);

        $this->postJson('/api/v1/visits', [
            'property_id' => $property->id,
            'customer_name' => 'بازدیدکننده',
            'customer_mobile' => '09127778899',
            'visit_at' => now()->addDay()->format('Y-m-d H:i:s'),
            'notes' => 'توسط مشاور',
        ])->assertCreated();

        $this->assertDatabaseHas('customers', [
            'office_id' => $this->office->id,
            'mobile' => '09127778899',
        ]);
    }
}
