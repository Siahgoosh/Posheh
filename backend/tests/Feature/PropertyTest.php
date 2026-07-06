<?php

namespace Tests\Feature;

use App\Enums\PropertyPermission;
use App\Enums\PropertyType;
use App\Enums\UserRole;
use App\Models\Office;
use App\Models\Property;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PropertyTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;
    private User $consultant;
    private Office $office;

    protected function setUp(): void
    {
        parent::setUp();

        $this->office = Office::create(['name' => 'Test Office', 'slug' => 'test-office', 'is_active' => true]);
        Wallet::create(['office_id' => $this->office->id, 'balance' => 0]);

        $plan = SubscriptionPlan::create([
            'slug' => 'basic',
            'name' => 'پایه',
            'max_users' => 3,
            'max_properties' => 100,
            'storage_gb' => 5,
            'monthly_price' => 990000,
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
            'mobile' => '09121111111',
            'office_id' => $this->office->id,
            'role' => UserRole::OfficeManager,
            'is_active' => true,
        ]);

        $this->consultant = User::create([
            'name' => 'Consultant',
            'mobile' => '09122222222',
            'office_id' => $this->office->id,
            'role' => UserRole::Consultant,
            'is_active' => true,
        ]);
    }

    public function test_consultant_can_create_property(): void
    {
        Sanctum::actingAs($this->consultant);

        $response = $this->postJson('/api/v1/properties', [
            'code' => 'A-1001',
            'type' => PropertyType::Sale->value,
            'permission' => PropertyPermission::Office->value,
            'price' => 5000000000,
            'area' => 120,
            'rooms' => 3,
            'city' => 'تهران',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.code', 'A-1001');

        $this->assertDatabaseHas('properties', ['code' => 'A-1001']);
    }

    public function test_consultant_cannot_see_private_property_of_others(): void
    {
        $privateProperty = Property::create([
            'office_id' => $this->office->id,
            'created_by' => $this->manager->id,
            'code' => 'PRIVATE-001',
            'type' => PropertyType::Sale,
            'permission' => PropertyPermission::Private,
            'price' => 3000000000,
        ]);

        Sanctum::actingAs($this->consultant);

        $response = $this->getJson("/api/v1/properties/{$privateProperty->id}");

        $response->assertStatus(422);
    }

    public function test_manager_can_see_all_properties(): void
    {
        $privateProperty = Property::create([
            'office_id' => $this->office->id,
            'created_by' => $this->consultant->id,
            'code' => 'PRIVATE-002',
            'type' => PropertyType::Rent,
            'permission' => PropertyPermission::Private,
            'rent' => 50000000,
        ]);

        Sanctum::actingAs($this->manager);

        $response = $this->getJson("/api/v1/properties/{$privateProperty->id}");

        $response->assertOk()
            ->assertJsonPath('data.code', 'PRIVATE-002');
    }
}
