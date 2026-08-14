<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

class CommunicationInstallCommand extends Command
{
    protected $signature = 'communication:install {--force : Run without confirmation}';

    protected $description = 'Install Communication module: migrate comm_* tables and seed permissions/settings';

    public function handle(): int
    {
        if (! $this->option('force') && ! $this->confirm('این دستور migration و seeder مرکز ارتباطات را اجرا می‌کند. ادامه؟', true)) {
            return self::SUCCESS;
        }

        $this->info('Running migrations...');
        Artisan::call('migrate', ['--force' => true]);
        $this->line(Artisan::output());

        $this->info('Seeding Communication module...');
        Artisan::call('db:seed', ['--class' => 'CommunicationSeeder', '--force' => true]);
        $this->line(Artisan::output());

        $this->info('Seeding communication system settings...');
        Artisan::call('db:seed', ['--class' => 'SystemSettingsSeeder', '--force' => true]);
        $this->line(Artisan::output());

        if (! Schema::hasTable('comm_visitors')) {
            $this->error('comm_visitors table still missing — check migration logs.');

            return self::FAILURE;
        }

        $this->info('Communication module installed successfully.');

        return self::SUCCESS;
    }
}
