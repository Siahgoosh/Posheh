<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        User::updateOrCreate(
            ['mobile' => '09170577873'],
            [
                'name' => 'مدیر پوشه',
                'role' => UserRole::SuperAdmin,
                'is_active' => true,
                'mobile_verified_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        // Keep user on rollback
    }
};
