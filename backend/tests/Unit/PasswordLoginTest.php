<?php

namespace Tests\Unit;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\Auth\PasswordAuthService;
use App\Services\Subscription\SubscriptionAccessService;
use Illuminate\Validation\ValidationException;
use Mockery;
use Tests\TestCase;

class PasswordLoginTest extends TestCase
{
    public function test_login_rejects_invalid_password(): void
    {
        $user = Mockery::mock(User::class)->makePartial();
        $user->id = 1;
        $user->password = bcrypt('correct-pass');
        $user->is_active = true;
        $user->shouldReceive('isSuperAdmin')->andReturn(true);
        $user->shouldReceive('update')->andReturn(true);
        $user->shouldReceive('createToken')->andReturn((object) [
            'plainTextToken' => 'token',
            'accessToken' => (object) ['expires_at' => null],
        ]);
        $user->shouldReceive('load')->andReturnSelf();
        $user->office = null;

        $repo = Mockery::mock(UserRepositoryInterface::class);
        $repo->shouldReceive('findByLogin')->with('user@test.com')->andReturn($user);

        $access = Mockery::mock(SubscriptionAccessService::class);
        $access->shouldReceive('userHasAccess')->andReturn(true);
        $access->shouldReceive('accessStatus')->andReturn([]);

        $service = new PasswordAuthService($repo, $access);

        $this->expectException(ValidationException::class);
        $service->login('user@test.com', 'wrong-pass');
    }
}
