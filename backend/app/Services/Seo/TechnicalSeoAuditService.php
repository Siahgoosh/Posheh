<?php

namespace App\Services\Seo;

use App\Models\BlogBrokenLink;
use App\Models\BlogPost;
use App\Models\BlogRedirect;
use App\Models\SeoTechnicalAudit;
use App\Services\Blog\BlogSitemapService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Internal technical SEO audit — never fabricates rankings/CWV/field data.
 */
class TechnicalSeoAuditService
{
    public function __construct(
        private readonly BlogSitemapService $sitemap,
        private readonly SitemapValidatorService $sitemapValidator,
    ) {}

    /**
     * @return array{status: string, summary: array<string,int>, issues: list<array<string,mixed>>, metrics: array<string,mixed>}
     */
    public function run(string $scope = 'manual'): array
    {
        $issues = [];
        $metrics = [
            'field_data' => 'UNKNOWN',
            'lab_data' => 'UNKNOWN',
            'cwv_source' => 'no_field_data — budgets are config targets only',
            'performance_budget' => config('performance.budget'),
            'url_policy' => config('performance.url_policy'),
            'ran_at' => now()->toIso8601String(),
        ];

        $this->auditRobots($issues);
        $this->auditUrlPolicy($issues);
        $this->auditPostsIndexability($issues, $metrics);
        $this->auditCanonicals($issues, $metrics);
        $this->auditRedirects($issues, $metrics);
        $this->auditTitlesMeta($issues, $metrics);
        $this->auditImages($issues, $metrics);
        $this->auditSoft404Signals($issues, $metrics);
        $this->auditSchemaSignals($issues, $metrics);
        $this->auditOrphans($issues, $metrics);
        $this->auditSecurityConfig($issues, $metrics);

        $sitemapReport = $this->sitemapValidator->validate();
        $metrics['sitemap'] = $sitemapReport['metrics'];
        foreach ($sitemapReport['issues'] as $issue) {
            $issues[] = $issue;
        }

        $summary = [
            'critical' => 0,
            'high' => 0,
            'medium' => 0,
            'low' => 0,
            'info' => 0,
        ];
        foreach ($issues as $issue) {
            $sev = strtolower((string) ($issue['severity'] ?? 'info'));
            if (isset($summary[$sev])) {
                $summary[$sev]++;
            }
        }

        $status = match (true) {
            $summary['critical'] > 0 => 'critical',
            $summary['high'] > 0 => 'warn',
            default => 'ok',
        };

        if (Schema::hasTable('seo_technical_audits')) {
            SeoTechnicalAudit::create([
                'scope' => $scope,
                'status' => $status,
                'summary' => $summary,
                'issues' => array_slice($issues, 0, 500),
                'metrics' => $metrics,
                'ran_at' => now(),
            ]);
        }

        return compact('status', 'summary', 'issues', 'metrics');
    }

    /** @param list<array<string,mixed>> $issues */
    private function auditRobots(array &$issues): void
    {
        $disallowedCritical = ['/blog', '/register', '/contact'];
        // Heuristic: ensure robots generator does not disallow blog root
        $controller = app(\App\Http\Controllers\BlogWebController::class);
        $body = $controller->robots()->getContent();
        foreach ($disallowedCritical as $path) {
            if (preg_match('/Disallow:\s*'.preg_quote($path, '/').'\s*$/mi', $body)
                && ! preg_match('/Allow:\s*'.preg_quote($path, '/').'/mi', $body)) {
                $issues[] = $this->issue('critical', 'robots', "robots.txt may block {$path}", $path);
            }
        }
        if (! str_contains($body, 'Sitemap:')) {
            $issues[] = $this->issue('high', 'robots', 'robots.txt missing Sitemap directive', '/robots.txt');
        }
        if (! str_contains($body, 'Disallow: /blog/search')) {
            $issues[] = $this->issue('medium', 'robots', 'Search results should be disallowed', '/blog/search');
        }
        $issues[] = $this->issue('info', 'robots', 'Dynamic robots.txt served by Laravel (canonical over SPA static copy)', '/robots.txt');
    }

    /** @param list<array<string,mixed>> $issues */
    private function auditUrlPolicy(array &$issues): void
    {
        $host = config('performance.url_policy.preferred_host');
        $scheme = config('performance.url_policy.preferred_scheme', 'https');
        $appUrl = (string) config('app.frontend_url', config('app.url'));
        if ($scheme === 'https' && str_starts_with($appUrl, 'http://')) {
            $issues[] = $this->issue('high', 'url', 'frontend_url is HTTP — prefer HTTPS canonical host', $appUrl);
        }
        if ($host && ! str_contains($appUrl, $host)) {
            $issues[] = $this->issue('medium', 'url', "Configured preferred host ({$host}) differs from app.frontend_url", $appUrl);
        }
    }

    /** @param list<array<string,mixed>> $issues @param array<string,mixed> $metrics */
    private function auditPostsIndexability(array &$issues, array &$metrics): void
    {
        $published = BlogPost::published()->count();
        $noindexPublished = BlogPost::published()
            ->where('robots_directive', 'like', '%noindex%')
            ->count();
        $missingMeta = BlogPost::published()
            ->where(fn ($q) => $q->whereNull('meta_description')->orWhere('meta_description', ''))
            ->count();
        $missingTitle = BlogPost::published()
            ->where(fn ($q) => $q->whereNull('meta_title')->orWhere('meta_title', ''))
            ->count();

        $metrics['posts'] = compact('published', 'noindexPublished', 'missingMeta', 'missingTitle');

        if ($noindexPublished > 0) {
            $issues[] = $this->issue('medium', 'indexability', "{$noindexPublished} published posts are noindex (verify intentional)", 'blog_posts');
        }
        if ($missingMeta > 0) {
            $issues[] = $this->issue('high', 'meta', "{$missingMeta} published posts missing meta description", 'blog_posts');
        }
        if ($missingTitle > 20) {
            $issues[] = $this->issue('medium', 'meta', "{$missingTitle} published posts missing meta_title (fall back to title)", 'blog_posts');
        }
    }

    /** @param list<array<string,mixed>> $issues @param array<string,mixed> $metrics */
    private function auditCanonicals(array &$issues, array &$metrics): void
    {
        $custom = BlogPost::published()->whereNotNull('canonical_url')->where('canonical_url', '!=', '')->get(['id', 'slug', 'canonical_url', 'robots_directive']);
        $conflicts = 0;
        foreach ($custom as $post) {
            $self = '/blog/'.$post->slug;
            $c = rtrim((string) $post->canonical_url, '/');
            if (! str_ends_with($c, $self) && $c !== $self) {
                $conflicts++;
                if ($conflicts <= 25) {
                    $issues[] = $this->issue('high', 'canonical', 'Canonical points away from self URL — excluded from sitemap if off-self', $self, [
                        'canonical' => $post->canonical_url,
                    ]);
                }
            }
            if (str_contains(strtolower((string) $post->robots_directive), 'noindex')) {
                $issues[] = $this->issue('high', 'canonical', 'Canonical candidate is noindex', $self);
            }
        }
        $metrics['canonical_overrides'] = $custom->count();
        $metrics['canonical_conflicts'] = $conflicts;
    }

    /** @param list<array<string,mixed>> $issues @param array<string,mixed> $metrics */
    private function auditRedirects(array &$issues, array &$metrics): void
    {
        $redirects = BlogRedirect::where('is_active', true)->get(['id', 'from_path', 'to_path', 'status_code']);
        $byFrom = $redirects->keyBy('from_path');
        $chains = 0;
        $loops = 0;
        foreach ($redirects as $r) {
            if ($r->from_path === $r->to_path) {
                $loops++;
                $issues[] = $this->issue('critical', 'redirect', 'Redirect loop (from=to)', $r->from_path);
                continue;
            }
            if ($byFrom->has($r->to_path)) {
                $chains++;
                $next = $byFrom->get($r->to_path);
                if ($next && $next->to_path === $r->from_path) {
                    $loops++;
                    $issues[] = $this->issue('critical', 'redirect', 'Redirect loop A↔B', $r->from_path, ['via' => $r->to_path]);
                } else {
                    $issues[] = $this->issue('high', 'redirect', 'Redirect chain detected — prefer A→C', $r->from_path, ['via' => $r->to_path]);
                }
            }
            if ((int) $r->status_code === 302) {
                $issues[] = $this->issue('low', 'redirect', 'Temporary 302 — use 301 for permanent moves', $r->from_path);
            }
        }
        $metrics['redirects'] = [
            'active' => $redirects->count(),
            'chains' => $chains,
            'loops' => $loops,
        ];
    }

    /** @param list<array<string,mixed>> $issues @param array<string,mixed> $metrics */
    private function auditTitlesMeta(array &$issues, array &$metrics): void
    {
        $dupTitles = BlogPost::published()
            ->selectRaw('title, count(*) as total')
            ->groupBy('title')
            ->havingRaw('count(*) > 1')
            ->limit(20)
            ->get();
        $metrics['duplicate_titles'] = $dupTitles->count();
        foreach ($dupTitles as $row) {
            $issues[] = $this->issue('high', 'title', 'Duplicate title among published posts', (string) $row->title, ['count' => $row->total]);
        }

        $dupMeta = BlogPost::published()
            ->whereNotNull('meta_description')
            ->where('meta_description', '!=', '')
            ->selectRaw('meta_description, count(*) as total')
            ->groupBy('meta_description')
            ->havingRaw('count(*) > 1')
            ->limit(15)
            ->get();
        foreach ($dupMeta as $row) {
            $issues[] = $this->issue('medium', 'meta', 'Duplicate meta description', Str::limit((string) $row->meta_description, 80), ['count' => $row->total]);
        }
    }

    /** @param list<array<string,mixed>> $issues @param array<string,mixed> $metrics */
    private function auditImages(array &$issues, array &$metrics): void
    {
        $missingCover = BlogPost::published()
            ->where(fn ($q) => $q->whereNull('cover_image')->orWhere('cover_image', ''))
            ->count();
        $metrics['missing_cover'] = $missingCover;
        if ($missingCover > 0) {
            $issues[] = $this->issue('medium', 'image', "{$missingCover} published posts missing featured image", 'cover_image');
        }
        $issues[] = $this->issue('info', 'image', 'Hero/cover should not be lazy-loaded (LCP). Body images may use lazy + width/height.', 'cwv');
        $issues[] = $this->issue('info', 'image', 'WebP/AVIF upload allowed; responsive srcset variants not fully automated for blog yet', 'format');
    }

    /** @param list<array<string,mixed>> $issues @param array<string,mixed> $metrics */
    private function auditSoft404Signals(array &$issues, array &$metrics): void
    {
        $thin = BlogPost::published()
            ->where(function ($q) {
                $q->whereNull('content')->orWhereRaw('CHAR_LENGTH(content) < 200');
            })
            ->limit(20)
            ->get(['id', 'slug', 'title']);
        $metrics['thin_content_candidates'] = $thin->count();
        foreach ($thin as $p) {
            $issues[] = $this->issue('high', 'soft404', 'Published post with very short content (soft-404 risk)', '/blog/'.$p->slug);
        }
    }

    /** @param list<array<string,mixed>> $issues @param array<string,mixed> $metrics */
    private function auditSchemaSignals(array &$issues, array &$metrics): void
    {
        $faqNoContent = BlogPost::published()
            ->whereNotNull('faq')
            ->where('faq', '!=', '[]')
            ->where(function ($q) {
                $q->whereNull('content')->orWhere('content', 'not like', '%faq%');
            })
            ->count();
        // Informational — FAQ may render from structured field even if HTML lacks "faq"
        $metrics['posts_with_faq'] = BlogPost::published()->whereNotNull('faq')->where('faq', '!=', '[]')->count();
        $issues[] = $this->issue('info', 'schema', 'SSR emits BlogPosting + Breadcrumb + FAQ only when FAQ present in page template', 'schema');
        unset($faqNoContent);
    }

    /** @param list<array<string,mixed>> $issues @param array<string,mixed> $metrics */
    private function auditOrphans(array &$issues, array &$metrics): void
    {
        $orphans = BlogPost::published()
            ->where(function ($q) {
                $q->whereNull('related_slugs')->orWhere('related_slugs', '[]');
            })
            ->whereDoesntHave('tags')
            ->count();
        $metrics['orphan_signal'] = $orphans;
        if ($orphans > 50) {
            $issues[] = $this->issue('medium', 'internal_links', "{$orphans} published posts lack related_slugs and tags (possible orphan risk — sitemap alone ≠ discovery)", 'internal_links');
        }
    }

    /** @param list<array<string,mixed>> $issues @param array<string,mixed> $metrics */
    private function auditSecurityConfig(array &$issues, array &$metrics): void
    {
        $metrics['security_headers'] = [
            'enabled' => (bool) config('performance.security_headers.enabled'),
            'hsts' => (bool) config('performance.security_headers.hsts'),
            'csp_enabled' => (bool) config('performance.security_headers.csp_enabled'),
            'note' => 'CSP off by default until staging validation',
        ];
        if (! config('performance.security_headers.enabled')) {
            $issues[] = $this->issue('medium', 'security', 'SecurityHeadersMiddleware disabled via config', 'headers');
        }
        if (config('performance.security_headers.csp_enabled')) {
            $issues[] = $this->issue('info', 'security', 'CSP is enabled — verify no broken assets in staging/prod', 'csp');
        } else {
            $issues[] = $this->issue('info', 'security', 'CSP not enabled (safe default). Nginx baseline headers present.', 'csp');
        }
    }

    /**
     * Optional live probe of critical URLs (skipped if outbound blocked).
     *
     * @return list<array<string,mixed>>
     */
    public function probeCriticalUrls(): array
    {
        $base = rtrim((string) config('app.frontend_url', config('app.url')), '/');
        $paths = ['/', '/blog', '/robots.txt', '/sitemap.xml', '/contact', '/login'];
        $out = [];
        foreach ($paths as $path) {
            $url = $base.$path;
            try {
                $res = Http::timeout(8)->withOptions(['allow_redirects' => false])->get($url);
                $out[] = [
                    'url' => $url,
                    'status' => $res->status(),
                    'ok' => $res->status() >= 200 && $res->status() < 400,
                ];
            } catch (\Throwable $e) {
                $out[] = [
                    'url' => $url,
                    'status' => null,
                    'ok' => false,
                    'error' => 'UNKNOWN — probe failed (network/egress)',
                ];
            }
        }

        return $out;
    }

    /** @return array{severity: string, category: string, message: string, url: string, meta?: array<string,mixed>} */
    private function issue(string $severity, string $category, string $message, string $url, array $meta = []): array
    {
        $row = compact('severity', 'category', 'message', 'url');
        if ($meta !== []) {
            $row['meta'] = $meta;
        }

        return $row;
    }
}
