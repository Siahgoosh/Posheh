<?php

namespace App\Console\Commands;

use App\Mail\DatabaseBackupMail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Process\Process;

class BackupDatabaseCommand extends Command
{
    protected $signature = 'backup:database {--email= : Override backup recipient email} {--no-email : Save file only, skip email}';

    protected $description = 'Create a MySQL dump, gzip it, and optionally email to the configured backup address';

    public function handle(): int
    {
        $recipient = $this->option('email') ?: env('BACKUP_EMAIL', 'hamidrezakeshavarziii9@gmail.com');

        if (! $recipient) {
            $this->error('BACKUP_EMAIL is not configured.');

            return self::FAILURE;
        }

        $dir = storage_path('app/backups');
        File::ensureDirectoryExists($dir);

        $timestamp = now()->format('Y-m-d_His');
        $sqlFile = "{$dir}/posheh-{$timestamp}.sql";
        $gzFile = "{$sqlFile}.gz";

        $host = config('database.connections.mysql.host');
        $port = config('database.connections.mysql.port');
        $database = config('database.connections.mysql.database');
        $username = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');

        $dumpCmd = sprintf(
            'mysqldump --host=%s --port=%s --user=%s --password=%s --single-transaction --routines --triggers %s > %s',
            escapeshellarg($host),
            escapeshellarg((string) $port),
            escapeshellarg($username),
            escapeshellarg($password),
            escapeshellarg($database),
            escapeshellarg($sqlFile),
        );

        $process = Process::fromShellCommandline($dumpCmd);
        $process->setTimeout(600);
        $process->run();

        if (! $process->isSuccessful() || ! File::exists($sqlFile) || filesize($sqlFile) < 100) {
            $this->error('mysqldump failed: '.$process->getErrorOutput());
            @unlink($sqlFile);

            return self::FAILURE;
        }

        $gzip = Process::fromShellCommandline('gzip -f '.escapeshellarg($sqlFile));
        $gzip->run();

        if (! File::exists($gzFile)) {
            $this->error('gzip failed.');

            return self::FAILURE;
        }

        $filename = basename($gzFile);
        $sizeMb = round(filesize($gzFile) / 1024 / 1024, 2);
        $this->info("Backup created: {$filename} ({$sizeMb} MB)");

        if ($this->option('no-email')) {
            $this->info("Saved locally: {$gzFile}");

            return self::SUCCESS;
        }

        try {
            Mail::to($recipient)->send(new DatabaseBackupMail($gzFile, $filename));
            $this->info("Backup emailed to {$recipient}");
        } catch (\Throwable $e) {
            $this->warn('Email failed: '.$e->getMessage());
            $this->info("Backup saved locally: {$gzFile}");

            return self::SUCCESS;
        }

        $this->cleanupOldBackups($dir, 10);

        return self::SUCCESS;
    }

    private function cleanupOldBackups(string $dir, int $keep): void
    {
        $files = collect(File::glob("{$dir}/*.gz"))->sort()->values();
        foreach ($files->slice(0, max(0, $files->count() - $keep)) as $old) {
            @unlink($old);
        }
    }
}
