<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;

class AuthDiagnoseCommand extends Command
{
    protected $signature = 'auth:diagnose';

    protected $description = 'Check password login routes and platform admin account';

    public function handle(): int
    {
        $loginRoute = collect(Route::getRoutes())->first(
            fn ($route) => $route->uri() === 'api/v1/auth/login' && in_array('POST', $route->methods(), true)
        );
        $capabilitiesRoute = collect(Route::getRoutes())->first(
            fn ($route) => $route->uri() === 'api/v1/auth/capabilities' && in_array('GET', $route->methods(), true)
        );

        $this->line(($loginRoute ? '✓' : '✗').' POST api/v1/auth/login');
        $this->line(($capabilitiesRoute ? '✓' : '✗').' GET api/v1/auth/capabilities');

        if (! $loginRoute || ! $capabilitiesRoute) {
            $this->error('Auth routes missing — run migrate, optimize:clear, and check routes/api.php loads without errors.');
            $this->line('Common fix: missing use import in routes/api.php (e.g. ConsultantDirectoryController).');

            return self::FAILURE;
        }

        $admins = User::query()
            ->whereIn('role', ['super_admin', 'platform_admin'])
            ->where('is_active', true)
            ->get(['id', 'email', 'username', 'mobile', 'role']);

        if ($admins->isEmpty()) {
            $this->warn('No active platform admin found.');
            $this->line('Run: php artisan auth:ensure-platform-admin');
        } else {
            $this->info('Platform staff accounts:');
            foreach ($admins as $admin) {
                $role = $admin->role instanceof \BackedEnum ? $admin->role->value : (string) $admin->role;
                $hasPassword = $admin->getRawOriginal('password') ? 'password set' : 'NO PASSWORD';
                $this->line("  #{$admin->id} {$admin->email} / {$admin->username} ({$role}) — {$hasPassword}");
            }
        }

        $this->info('Auth module looks OK.');

        return self::SUCCESS;
    }
}
