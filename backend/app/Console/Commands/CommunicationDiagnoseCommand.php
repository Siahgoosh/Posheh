<?php

namespace App\Console\Commands;

use App\Modules\Communication\Application\DTOs\CaptureLeadDTO;
use App\Modules\Communication\Application\DTOs\VisitorInitDTO;
use App\Modules\Communication\Application\Services\LeadCaptureService;
use App\Modules\Communication\Application\Services\VisitorTrackingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class CommunicationDiagnoseCommand extends Command
{
    protected $signature = 'communication:diagnose';

    protected $description = 'Test communication visitor init + lead capture flow';

    public function handle(
        VisitorTrackingService $visitors,
        LeadCaptureService $leads,
    ): int {
        $tables = ['comm_visitors', 'comm_visitor_sessions', 'comm_leads', 'comm_conversations', 'comm_messages'];
        foreach ($tables as $table) {
            $ok = Schema::hasTable($table);
            $this->line(($ok ? '✓' : '✗')." {$table}");
            if (! $ok) {
                $this->error('Missing tables — run: php artisan communication:install');

                return self::FAILURE;
            }
        }

        $sessionKey = 'diag-'.Str::random(8);

        try {
            $init = $visitors->init(VisitorInitDTO::fromRequest(
                [
                    'session_key' => $sessionKey,
                    'current_page' => '/',
                ],
                '127.0.0.1',
                'CommunicationDiagnose/1.0',
                null,
            ));
            $this->info('Init OK — visitor_token: '.$init['visitor_token']);
        } catch (\Throwable $e) {
            $this->error('Init FAILED: '.$e->getMessage());

            return self::FAILURE;
        }

        try {
            $capture = $leads->capture(CaptureLeadDTO::fromRequest(
                [
                    'visitor_token' => $init['visitor_token'],
                    'session_key' => $sessionKey,
                    'first_name' => 'تست',
                    'mobile' => '09120000000',
                    'description' => 'diagnose',
                ],
                '127.0.0.1',
                'CommunicationDiagnose/1.0',
            ));
            $this->info('Capture OK — conversation: '.$capture['conversation_uuid']);
        } catch (\Throwable $e) {
            $this->error('Capture FAILED: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info('Communication module is working.');

        return self::SUCCESS;
    }
}
