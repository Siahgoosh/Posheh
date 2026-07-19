<?php

namespace Tests\Feature;

use App\Models\Office;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanFeatureMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_accounting_blocked_without_feature(): void
    {
        $plan = SubscriptionPlan::create([
            'slug' => 'solo-test',
            'panel_type' => 'solo',
            'name' => 'Solo',
            'monthly_price' => 1000,
            'max_users' => 1,
            'max_properties' => 10,
            'features' => ['filing', 'crm'],
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $office = Office::create([
            'name' => 'Test Office',
            'slug' => 'test-office',
            'subscription_plan_id' => $plan->id,
            'plan_active' => true,
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'office_id' => $office->id,
            'role' => 'office_manager',
        ]);

        Subscription::create([
            'office_id' => $office->id,
            'subscription_plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
        ]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/accounting')
            ->assertForbidden()
            ->assertJsonPath('feature', 'accounting');
    }
}
