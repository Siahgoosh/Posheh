<?php

namespace App\Services\Seo;

use App\Models\BlogGscMetric;
use App\Models\Seo\SeoAlert;
use App\Models\Seo\SeoPageDaily;
use App\Models\Seo\SeoSearchQuery;
use App\Services\Blog\PersianTextNormalizer;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Collects Search Console metrics when credentials exist.
 * Never invents clicks/impressions — returns DATA_UNAVAILABLE otherwise.
 */
class SeoGscCollector
{
    public function __construct(
        private readonly PersianTextNormalizer $normalizer,
        private readonly SeoQueryIntelligence $intelligence,
    ) {}

    /** @return array{status: string, message: string, rows_upserted: int} */
    public function collect(?Carbon $start = null, ?Carbon $end = null): array
    {
        if (! Schema::hasTable('blog_gsc_metrics')) {
            return ['status' => 'DATA_UNAVAILABLE', 'message' => 'blog_gsc_metrics table missing', 'rows_upserted' => 0];
        }

        if (! config('blog.gsc.enabled')) {
            $this->alert('medium', 'gsc_disabled', 'GSC collection disabled', 'Set BLOG_GSC_ENABLED=1 when credentials are ready.');

            return ['status' => 'DATA_UNAVAILABLE', 'message' => 'GSC disabled (BLOG_GSC_ENABLED=false). No fake data generated.', 'rows_upserted' => 0];
        }

        $credentials = (string) config('blog.gsc.credentials_json');
        if ($credentials === '' || ! is_file($credentials)) {
            $this->alert('high', 'gsc_credentials', 'GSC credentials missing', 'BLOG_GSC_CREDENTIALS_JSON path is empty or invalid.');

            return ['status' => 'DATA_UNAVAILABLE', 'message' => 'Credentials missing — DATA_UNAVAILABLE', 'rows_upserted' => 0];
        }

        $start ??= now()->subDays(3)->startOfDay();
        $end ??= now()->subDay()->endOfDay();

        try {
            $accessToken = $this->accessTokenFromServiceAccount($credentials);
            if (! $accessToken) {
                return ['status' => 'DATA_UNAVAILABLE', 'message' => 'Could not obtain GSC access token', 'rows_upserted' => 0];
            }

            $property = rtrim((string) config('blog.gsc.property'), '/').'/';
            $encoded = rawurlencode($property);
            $url = "https://www.googleapis.com/webmasters/v3/sites/{$encoded}/searchAnalytics/query";

            $response = Http::withToken($accessToken)
                ->timeout(30)
                ->retry(2, 500)
                ->post($url, [
                    'startDate' => $start->toDateString(),
                    'endDate' => $end->toDateString(),
                    'dimensions' => ['query', 'page', 'device', 'country'],
                    'rowLimit' => 5000,
                ]);

            if (! $response->successful()) {
                Log::warning('GSC API failure', ['status' => $response->status(), 'body' => $response->body()]);
                $this->alert('high', 'gsc_api_failure', 'GSC API failure', 'HTTP '.$response->status());

                return ['status' => 'DATA_UNAVAILABLE', 'message' => 'GSC API failed — blog remains healthy', 'rows_upserted' => 0];
            }

            $rows = $response->json('rows') ?? [];
            $upserted = 0;
            foreach ($rows as $row) {
                $keys = $row['keys'] ?? [];
                $query = (string) ($keys[0] ?? '');
                $page = (string) ($keys[1] ?? '');
                $device = (string) ($keys[2] ?? '');
                $country = (string) ($keys[3] ?? '');
                $norm = $this->intelligence->normalizeQuery($query);

                BlogGscMetric::query()->updateOrCreate(
                    [
                        'page_url' => $page,
                        'query' => $norm['query_normalized'],
                        'date' => $end->toDateString(),
                        'device' => $device,
                        'country' => $country,
                    ],
                    [
                        'query_raw' => $norm['query_raw'],
                        'query_normalized' => $norm['query_normalized'],
                        'clicks' => (int) ($row['clicks'] ?? 0),
                        'impressions' => (int) ($row['impressions'] ?? 0),
                        'ctr' => isset($row['ctr']) ? round((float) $row['ctr'], 4) : null,
                        'position' => isset($row['position']) ? round((float) $row['position'], 2) : null,
                        'synced_at' => now(),
                    ]
                );
                $upserted++;
            }

            $this->rebuildAggregates();

            return ['status' => 'OK', 'message' => "Synced {$upserted} rows", 'rows_upserted' => $upserted];
        } catch (\Throwable $e) {
            Log::error('GSC collect exception', ['error' => $e->getMessage()]);
            $this->alert('critical', 'gsc_exception', 'GSC collector exception', $e->getMessage());

            return ['status' => 'DATA_UNAVAILABLE', 'message' => 'Exception: '.$e->getMessage(), 'rows_upserted' => 0];
        }
    }

    public function rebuildAggregates(): void
    {
        if (! Schema::hasTable('blog_gsc_metrics')) {
            return;
        }

        $since = now()->subDays(28)->toDateString();
        $rows = BlogGscMetric::query()
            ->where('date', '>=', $since)
            ->selectRaw('query_normalized, max(query_raw) as query_raw, sum(clicks) as clicks, sum(impressions) as impressions, avg(position) as position, max(page_url) as page_url, max(date) as last_date')
            ->groupBy('query_normalized')
            ->get();

        foreach ($rows as $row) {
            if (! $row->query_normalized) {
                continue;
            }
            $class = $this->intelligence->classify((string) ($row->query_raw ?: $row->query_normalized));
            $ctr = $row->impressions > 0 ? round($row->clicks / $row->impressions, 4) : null;
            SeoSearchQuery::query()->updateOrCreate(
                ['query_normalized' => $row->query_normalized],
                [
                    'query_raw' => $row->query_raw ?: $row->query_normalized,
                    'intent' => $class['intent'],
                    'topic' => $class['topic'],
                    'entity' => $class['entity'],
                    'funnel_stage' => $class['funnel_stage'],
                    'business_value' => $class['business_value'],
                    'current_page_url' => $row->page_url,
                    'cluster_key' => $this->intelligence->clusterKey($row->query_normalized, $class['intent'], $class['topic']),
                    'clicks_28d' => (int) $row->clicks,
                    'impressions_28d' => (int) $row->impressions,
                    'ctr_28d' => $ctr,
                    'position_28d' => $row->position ? round((float) $row->position, 2) : null,
                    'last_seen_at' => $row->last_date ? Carbon::parse($row->last_date) : now(),
                ]
            );
        }

        $pages = BlogGscMetric::query()
            ->where('date', '>=', $since)
            ->selectRaw('date, page_url, sum(clicks) as clicks, sum(impressions) as impressions, avg(position) as position')
            ->groupBy('date', 'page_url')
            ->get();

        foreach ($pages as $page) {
            $slug = $this->slugFromUrl((string) $page->page_url);
            $ctr = $page->impressions > 0 ? round($page->clicks / $page->impressions, 4) : null;
            SeoPageDaily::query()->updateOrCreate(
                ['date' => $page->date, 'page_url' => $page->page_url],
                [
                    'slug' => $slug,
                    'clicks' => (int) $page->clicks,
                    'impressions' => (int) $page->impressions,
                    'ctr' => $ctr,
                    'position' => $page->position ? round((float) $page->position, 2) : null,
                ]
            );
        }
    }

    private function slugFromUrl(string $url): ?string
    {
        if (preg_match('#/blog/([a-z0-9\-]+)/?#i', $url, $m)) {
            return $m[1];
        }

        return null;
    }

    private function accessTokenFromServiceAccount(string $path): ?string
    {
        $json = json_decode((string) file_get_contents($path), true);
        if (! is_array($json) || empty($json['client_email']) || empty($json['private_key'])) {
            return null;
        }

        // Prefer google/apiclient if installed; otherwise JWT assertion via openssl.
        if (class_exists(\Google\Client::class)) {
            $client = new \Google\Client;
            $client->setAuthConfig($json);
            $client->addScope('https://www.googleapis.com/auth/webmasters.readonly');
            $token = $client->fetchAccessTokenWithAssertion();

            return $token['access_token'] ?? null;
        }

        return $this->jwtAccessToken($json);
    }

    /** @param array<string, mixed> $sa */
    private function jwtAccessToken(array $sa): ?string
    {
        $now = time();
        $header = $this->b64(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $claim = $this->b64(json_encode([
            'iss' => $sa['client_email'],
            'scope' => 'https://www.googleapis.com/auth/webmasters.readonly',
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600,
        ]));
        $unsigned = $header.'.'.$claim;
        $key = openssl_pkey_get_private($sa['private_key']);
        if (! $key) {
            return null;
        }
        openssl_sign($unsigned, $signature, $key, OPENSSL_ALGO_SHA256);
        $jwt = $unsigned.'.'.$this->b64($signature);

        $res = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt,
        ]);

        return $res->successful() ? ($res->json('access_token') ?: null) : null;
    }

    private function b64(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function alert(string $severity, string $kind, string $title, string $detail): void
    {
        if (! Schema::hasTable('seo_alerts')) {
            return;
        }
        SeoAlert::query()->create([
            'severity' => $severity,
            'kind' => $kind,
            'title' => $title,
            'detail' => $detail,
            'is_resolved' => false,
        ]);
    }
}
