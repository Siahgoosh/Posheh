<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlogBrokenLink;
use App\Models\BlogRedirect;
use App\Models\SeoTechnicalAudit;
use App\Services\Seo\BrokenLinkScannerService;
use App\Services\Seo\SitemapValidatorService;
use App\Services\Seo\TechnicalSeoAuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class TechnicalSeoAdminController extends Controller
{
    public function __construct(
        private readonly TechnicalSeoAuditService $audit,
        private readonly SitemapValidatorService $sitemapValidator,
        private readonly BrokenLinkScannerService $links,
    ) {}

    public function dashboard(): JsonResponse
    {
        $latest = Schema::hasTable('seo_technical_audits')
            ? SeoTechnicalAudit::query()->orderByDesc('ran_at')->orderByDesc('id')->first()
            : null;

        $broken = Schema::hasTable('blog_broken_links')
            ? BlogBrokenLink::query()->where('is_resolved', false)->orderByDesc('last_checked_at')->limit(30)->get()
            : collect();

        return response()->json([
            'data' => [
                'latest_audit' => $latest,
                'broken_links' => $broken,
                'redirects_active' => BlogRedirect::where('is_active', true)->count(),
                'performance_budget' => config('performance.budget'),
                'url_policy' => config('performance.url_policy'),
                'security_headers' => config('performance.security_headers'),
                'field_data' => 'UNKNOWN',
                'lab_data' => 'UNKNOWN',
                'note' => 'Internal technical guidance — not Google Score / not CrUX field data.',
            ],
        ]);
    }

    public function run(Request $request): JsonResponse
    {
        $scope = $request->input('scope', 'manual');
        $result = $this->audit->run((string) $scope);
        if ($request->boolean('probe')) {
            $result['metrics']['probes'] = $this->audit->probeCriticalUrls();
        }
        if ($request->boolean('scan_links')) {
            $result['metrics']['broken_link_scan'] = $this->links->scan(50, false);
        }

        return response()->json(['data' => $result]);
    }

    public function validateSitemap(): JsonResponse
    {
        return response()->json(['data' => $this->sitemapValidator->validate()]);
    }

    public function history(): JsonResponse
    {
        if (! Schema::hasTable('seo_technical_audits')) {
            return response()->json(['data' => []]);
        }

        return response()->json([
            'data' => SeoTechnicalAudit::query()->orderByDesc('id')->limit(30)->get(),
        ]);
    }

    public function resolveBrokenLink(Request $request, int $id): JsonResponse
    {
        $link = BlogBrokenLink::findOrFail($id);
        $link->update(['is_resolved' => true]);

        return response()->json(['data' => $link]);
    }
}
