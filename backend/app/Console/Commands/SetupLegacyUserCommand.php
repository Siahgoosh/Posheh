<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class SetupLegacyUserCommand extends Command
{
    protected $signature = 'auth:setup-legacy-user
                            {mobile : Mobile e.g. 09170577873}
                            {email : User email}
                            {username : Username}
                            {--password= : Password (prompt if omitted)}';

    protected $description = 'Set email, username and password for a legacy mobile-only user';

    public function handle(): int
    {
        $mobile = preg_replace('/\D/', '', (string) $this->argument('mobile'));
        if (str_starts_with($mobile, '98')) {
            $mobile = '0'.substr($mobile, 2);
        }

        $user = User::where('mobile', $mobile)->first();
        if (! $user) {
            $this->error("User with mobile {$mobile} not found.");

            return self::FAILURE;
        }

        $password = (string) ($this->option('password') ?: $this->secret('Password'));
        if (strlen($password) < 8) {
            $this->error('Password must be at least 8 characters.');

            return self::FAILURE;
        }

        $user->update([
            'email' => strtolower((string) $this->argument('email')),
            'username' => strtolower((string) $this->argument('username')),
            'password' => Hash::make($password),
            'email_verified_at' => now(),
        ]);

        $this->info("Updated user #{$user->id} ({$user->name})");
        $this->line("  email: {$user->email}");
        $this->line("  username: {$user->username}");

        return self::SUCCESS;
    }
}
