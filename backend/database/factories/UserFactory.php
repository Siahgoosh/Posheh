<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'mobile' => '09'.fake()->unique()->numerify('#########'),
            'email' => fake()->optional()->safeEmail(),
            'role' => UserRole::Consultant,
            'is_active' => true,
            'mobile_verified_at' => now(),
            'remember_token' => Str::random(10),
        ];
    }
}
