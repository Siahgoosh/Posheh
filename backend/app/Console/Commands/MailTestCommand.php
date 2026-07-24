<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class MailTestCommand extends Command
{
    protected $signature = 'system:mail-test {to : Recipient email address}';

    protected $description = 'Send a test email using current MAIL_* settings';

    public function handle(): int
    {
        $to = (string) $this->argument('to');
        $from = config('mail.from.address');
        $mailer = config('mail.default');

        $this->info("Mailer: {$mailer}");
        $this->info("From: {$from}");
        $this->info("To: {$to}");

        if ($mailer === 'log') {
            $this->warn('MAIL_MAILER=log — email will only be written to storage/logs, not sent.');
        }

        try {
            Mail::raw('تست ایمیل پوشه — '.now()->toDateTimeString(), function ($message) use ($to) {
                $message->to($to)->subject('تست ایمیل پوشه');
            });
            $this->info('Test email dispatched successfully.');

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Failed: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
