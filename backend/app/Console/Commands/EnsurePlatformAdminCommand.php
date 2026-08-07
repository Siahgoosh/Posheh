<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Command;

class EnsurePlatformAdminCommand extends Command
{
    protected $signature = 'auth:ensure-platform-admin
                            {--email=info@posheapp.ir : Admin email}
                            {--username=posheh : Admin username}
                            {--mobile=09170577873 : Admin mobile}
                            {--name=مدیر پوشه : Display name}
                            {--password= : Password (or SEED_ADMIN_PASSWORD env)}';

    protected $description = 'Create or reset the platform super_admin used for panel.posheapp.ir login';

    public function handle(): int
    {
        $email = strtolower(trim((string) $this->option('email')));
        $username = strtolower(trim((string) $this->option('username')));
        $mobile = $this->normalizeMobile((string) $this->option('mobile'));
        $name = (string) $this->option('name');

        $password = (string) ($this->option('password') ?: env('SEED_ADMIN_PASSWORD', 'Posheh@2026'));
        if (strlen($password) < 8) {
            $this->error('Password must be at least 8 characters (set SEED_ADMIN_PASSWORD).');

            return self::FAILURE;
        }

        $user = User::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->orWhereRaw('LOWER(username) = ?', [$username])
            ->orWhere('mobile', $mobile)
            ->first();

        if ($user) {
            $user->update([
                'name' => $name,
                'email' => $email,
                'username' => $username,
                'mobile' => $mobile,
                'password' => $password,
                'role' => UserRole::SuperAdmin,
                'is_active' => true,
                'email_verified_at' => $user->email_verified_at ?? now(),
                'mobile_verified_at' => $user->mobile_verified_at ?? now(),
            ]);
            $this->info("Updated platform admin user #{$user->id}");
        } else {
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'username' => $username,
                'mobile' => $mobile,
                'password' => $password,
                'role' => UserRole::SuperAdmin,
                'is_active' => true,
                'email_verified_at' => now(),
                'mobile_verified_at' => now(),
            ]);
            $this->info("Created platform admin user #{$user->id}");
        }

        $this->line("  email: {$email}");
        $this->line("  username: {$username}");
        $this->line('  panel: https://panel.posheapp.ir/login');

        return self::SUCCESS;
    }

    private function normalizeMobile(string $mobile): string
    {
        $digits = preg_replace('/\D/', '', $mobile);
        if (str_starts_with($digits, '98')) {
            $digits = '0'.substr($digits, 2);
        }
        if ($digits !== '' && ! str_starts_with($digits, '0')) {
            $digits = '0'.$digits;
        }

        return $digits;
    }
}
