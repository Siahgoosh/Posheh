<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Office;
use App\Models\SystemSetting;
use App\Models\User;
use Database\Seeders\SystemSettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminSettingsTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemSettingsSeeder::class);

        $office = Office::create(['name' => 'HQ', 'slug' => 'hq']);
        $this->superAdmin = User::create([
            'name' => 'Super Admin',
            'mobile' => '09170577873',
            'office_id' => $office->id,
            'role' => UserRole::SuperAdmin,
            'is_active' => true,
        ]);
    }

    public function test_super_admin_can_save_api_key_and_see_has_value_flag(): void
    {
        Sanctum::actingAs($this->superAdmin);

        $response = $this->putJson('/api/v1/admin/settings', [
            'settings' => [
                'ippanel_api_key' => 'test-api-key-12345',
                'ippanel_from_number' => '+983000505',
                'sms_mode' => 'live',
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('sms_status.has_api_key', true)
            ->assertJsonPath('sms_status.has_from_number', true)
            ->assertJsonPath('sms_status.is_ready', true);

        $this->assertDatabaseHas('system_settings', [
            'key' => 'ippanel_api_key',
            'value' => 'test-api-key-12345',
        ]);

        $getResponse = $this->getJson('/api/v1/admin/settings');
        $getResponse->assertOk();

        $allSettings = collect($getResponse->json('data'))->flatten(1);
        $apiKeySetting = $allSettings->firstWhere('key', 'ippanel_api_key');

        $this->assertNotNull($apiKeySetting);
        $this->assertSame('********', $apiKeySetting['value']);
        $this->assertTrue($apiKeySetting['has_value']);
    }

    public function test_masked_secret_is_not_overwritten_on_save(): void
    {
        SystemSetting::where('key', 'ippanel_api_key')->update(['value' => 'existing-secret']);

        Sanctum::actingAs($this->superAdmin);

        $response = $this->putJson('/api/v1/admin/settings', [
            'settings' => [
                'ippanel_api_key' => '********',
                'sms_mode' => 'live',
            ],
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('system_settings', [
            'key' => 'ippanel_api_key',
            'value' => 'existing-secret',
        ]);
    }

    public function test_non_super_admin_cannot_access_settings(): void
    {
        $office = Office::create(['name' => 'Office', 'slug' => 'office']);
        $user = User::create([
            'name' => 'Manager',
            'mobile' => '09121111111',
            'office_id' => $office->id,
            'role' => UserRole::OfficeManager,
            'is_active' => true,
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/admin/settings')->assertForbidden();
    }
}
