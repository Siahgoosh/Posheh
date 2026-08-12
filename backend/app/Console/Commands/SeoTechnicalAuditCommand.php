<?php

namespace App\Console\Commands;

use App\Services\Seo\BrokenLinkScannerService;
use App\Services\Seo\TechnicalSeoAuditService;
use Illuminate\Console\Command;

class SeoTechnicalAuditCommand extends Command
{
    protected $signature = 'seo:technical-audit
        {--scope=manual : daily|weekly|manual}
        {--probe : Probe critical URLs over HTTP}
        {--scan-links : Also scan internal broken links}';

    protected $description = 'Run technical SEO / crawlability / indexability audit (no fake rankings/CWV)';

    public function handle(TechnicalSeoAuditService $audit, BrokenLinkScannerService $links): int
    {
        $scope = (string) $this->option('scope');
        $this->info("Running technical audit (scope={$scope})…");
        $result = $audit->run($scope);

        if ($this->option('probe')) {
            $probes = $audit->probeCriticalUrls();
            $result['metrics']['probes'] = $probes;
            $this->table(['URL', 'Status', 'OK'], collect($probes)->map(fn ($p) => [
                $p['url'],
                $p['status'] ?? 'UNKNOWN',
                ($p['ok'] ?? false) ? 'yes' : 'no',
            ])->all());
        }

        if ($this->option('scan-links')) {
            $scan = $links->scan(80, false);
            $result['metrics']['broken_link_scan'] = $scan;
            $this->info("Broken link scan: checked={$scan['checked']} broken={$scan['broken']}");
        }

        $this->info("Status: {$result['status']}");
        $this->table(
            ['Severity', 'Count'],
            collect($result['summary'])->map(fn ($v, $k) => [$k, $v])->values()->all()
        );

        $critical = collect($result['issues'])->where('severity', 'critical')->take(10);
        foreach ($critical as $issue) {
            $this->error("[{$issue['category']}] {$issue['message']} — {$issue['url']}");
        }

        return $result['status'] === 'critical' ? self::FAILURE : self::SUCCESS;
    }
}
