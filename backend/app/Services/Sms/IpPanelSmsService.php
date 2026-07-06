<?php

namespace App\Services\Sms;

use App\Services\Settings\SystemSettingsService;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class IpPanelSmsService
{
  private const RETRYABLE_STATUSES = [502, 503, 504, 520, 521, 522, 524];

  public function __construct(
    private readonly SystemSettingsService $settings,
  ) {}

  public function sendOtp(string $mobile, string $code): bool
  {
    if (! $this->settings->isSmsLive()) {
      Log::info("OTP SMS [log] to {$mobile}: {$code}");

      return true;
    }

    $config = $this->settings->ippanelConfig();

    if (! $this->hasCredentials($config)) {
      Log::warning('IPPanel SMS not configured', ['mobile' => $mobile, 'code' => $code]);

      return false;
    }

    if (! empty($config['otp_pattern_code'])) {
      return $this->sendPattern($mobile, $config['otp_pattern_code'], ['code' => $code], $config)['success'];
    }

    return $this->sendWebservice($mobile, "کد تأیید پوشه: {$code}", $config)['success'];
  }

  public function sendInvite(string $mobile, string $officeName, string $inviterName): bool
  {
    $template = $this->settings->get(
      'invite_sms_template',
      'شما به دفتر {office} در پوشه دعوت شدید. با شماره موبایل خود وارد شوید.'
    );

    $message = str_replace(['{office}', '{inviter}'], [$officeName, $inviterName], $template);

    if (! $this->settings->isSmsLive()) {
      Log::info("Invite SMS [log] to {$mobile}: {$message}");

      return true;
    }

    $config = $this->settings->ippanelConfig();

    if (! empty($config['invite_pattern_code'])) {
      return $this->sendPattern($mobile, $config['invite_pattern_code'], ['office' => $officeName], $config)['success'];
    }

    return $this->sendWebservice($mobile, $message, $config)['success'];
  }

  public function test(string $mobile, string $message = 'تست پیامک پوشه', ?array $configOverride = null): array
  {
    $config = $configOverride
      ? array_merge($this->settings->ippanelConfig(), $configOverride)
      : $this->settings->ippanelConfig();

    if (! $this->hasCredentials($config)) {
      return [
        'success' => false,
        'message' => 'تنظیمات IPPanel ناقص است. کلید API یا نام کاربری/رمز عبور را وارد و ذخیره کنید.',
      ];
    }

    if (empty($config['from_number'])) {
      return [
        'success' => false,
        'message' => 'شماره ارسال‌کننده (from_number) الزامی است. مثال: +983000505',
      ];
    }

    $result = $this->sendWebservice($mobile, $message, $config, forceLive: true);

    return $result['success']
      ? ['success' => true, 'message' => 'پیامک تست با موفقیت ارسال شد.', 'details' => $result['details'] ?? null, 'method' => $result['method'] ?? null]
      : ['success' => false, 'message' => $result['message'] ?? 'خطا در ارسال پیامک', 'details' => $result['details'] ?? null, 'method' => $result['method'] ?? null];
  }

  private function sendWebservice(string $mobile, string $message, array $config, bool $forceLive = false): array
  {
    if (! $forceLive && ! $this->settings->isSmsLive()) {
      Log::info("SMS [log] to {$mobile}: {$message}");

      return ['success' => true, 'message' => 'logged', 'method' => 'log'];
    }

    $auth = $this->resolveAuth($config);
    if (! $auth['token'] && ! $auth['api_key']) {
      return ['success' => false, 'message' => $auth['error'] ?? 'خطا در احراز هویت IPPanel'];
    }

    $mode = $config['api_mode'] ?? $this->settings->get('ippanel_api_mode', 'auto');
    $strategies = $this->webserviceStrategies($config, $mobile, $message, $auth, $mode);
    $lastResult = ['success' => false, 'message' => 'هیچ روش ارسالی موفق نشد'];

    foreach ($strategies as $strategy) {
      try {
        $response = $this->executeStrategy($strategy);
        $result = $this->parseResponse($response);
        $result['method'] = $strategy['name'];

        if ($result['success']) {
          Log::info('IPPanel SMS sent', ['method' => $strategy['name'], 'mobile' => $mobile]);

          return $result;
        }

        $lastResult = $result;

        if (! $this->shouldRetryWithNextStrategy($response, $mode)) {
          break;
        }

        Log::warning('IPPanel strategy failed, trying next', [
          'method' => $strategy['name'],
          'status' => $response->status(),
          'message' => $result['message'] ?? null,
        ]);
      } catch (\Throwable $e) {
        $lastResult = ['success' => false, 'message' => 'خطای اتصال: '.$e->getMessage(), 'method' => $strategy['name']];
        Log::error('IPPanel webservice exception', ['method' => $strategy['name'], 'error' => $e->getMessage()]);
      }
    }

    return $lastResult;
  }

  private function sendPattern(string $mobile, string $patternCode, array $params, array $config): array
  {
    $auth = $this->resolveAuth($config);
    if (! $auth['token'] && ! $auth['api_key']) {
      return ['success' => false, 'message' => $auth['error'] ?? 'خطا در احراز هویت IPPanel'];
    }

    $baseUrl = $this->apiBase($config);
    $token = $auth['token'] ?? $auth['api_key'];

    try {
      $response = Http::timeout(30)
        ->withHeaders([
          'Authorization' => $token,
          'Content-Type' => 'application/json',
          'Accept' => 'application/json',
        ])
        ->post("{$baseUrl}/send", [
          'sending_type' => 'pattern',
          'from_number' => $this->normalizeSender($config['from_number']),
          'code' => $patternCode,
          'recipients' => [$this->toE164($mobile)],
          'params' => $params,
        ]);

      $result = $this->parseResponse($response);
      $result['method'] = 'edge_pattern';

      if ($result['success'] || ! $this->shouldRetryWithNextStrategy($response, 'auto')) {
        return $result;
      }
    } catch (\Throwable $e) {
      Log::error('IPPanel pattern exception', ['error' => $e->getMessage()]);
    }

    // Legacy pattern fallback via GET
    return $this->sendPatternLegacy($mobile, $patternCode, $params, $config, $auth);
  }

  private function sendPatternLegacy(string $mobile, string $patternCode, array $params, array $config, array $auth): array
  {
    $bases = $this->legacyBases($config);
    $query = [
      'from' => $this->normalizeSender($config['from_number']),
      'to' => $this->toE164($mobile),
      'pattern_code' => $patternCode,
    ];

    foreach ($params as $key => $value) {
      $query["param_{$key}"] = $value;
    }

    if ($auth['api_key']) {
      $query['apikey'] = $auth['api_key'];
    } else {
      $query['username'] = $config['username'];
      $query['password'] = $config['password'];
    }

    foreach ($bases as $base) {
      $apiRoot = str_ends_with($base, '/api') ? $base : rtrim($base, '/').'/api';
      try {
        $response = Http::timeout(30)->acceptJson()->get("{$apiRoot}/send/pattern", $query);
        $result = $this->parseResponse($response);
        $result['method'] = 'legacy_pattern';
        if ($result['success']) {
          return $result;
        }
      } catch (\Throwable) {
        continue;
      }
    }

    return ['success' => false, 'message' => 'ارسال پترن ناموفق بود'];
  }

  /** @return list<array{name: string, type: string, url: string, options: array}> */
  private function webserviceStrategies(array $config, string $mobile, string $message, array $auth, string $mode): array
  {
    $token = $auth['token'] ?? $auth['api_key'];
    $from = $this->normalizeSender($config['from_number']);
    $to = $this->toE164($mobile);
    $edgeBase = $this->apiBase($config);

    $edgePost = [
      'name' => 'edge_post',
      'type' => 'post',
      'url' => "{$edgeBase}/send",
      'options' => [
        'headers' => [
          'Authorization' => $token,
          'Content-Type' => 'application/json',
          'Accept' => 'application/json',
        ],
        'json' => [
          'sending_type' => 'webservice',
          'from_number' => $from,
          'message' => $message,
          'params' => ['recipients' => [$to]],
        ],
      ],
    ];

    $legacyQueries = [
      'from' => $from,
      'message' => $message,
      'to' => $to,
    ];

    if ($auth['api_key']) {
      $legacyQueries['apikey'] = $auth['api_key'];
    } else {
      $legacyQueries['username'] = $config['username'];
      $legacyQueries['password'] = $config['password'];
    }

    $legacyStrategies = [];
    foreach ($this->legacyBases($config) as $base) {
      $apiRoot = str_ends_with($base, '/api') ? $base : rtrim($base, '/').'/api';
      $legacyStrategies[] = [
        'name' => 'legacy_get_'.str_replace(['https://', '/', '.'], ['', '_', '_'], $apiRoot),
        'type' => 'get',
        'url' => "{$apiRoot}/send/webservice",
        'options' => ['query' => $legacyQueries],
      ];
    }

    return match ($mode) {
      'edge' => [$edgePost],
      'legacy' => $legacyStrategies,
      default => [$edgePost, ...$legacyStrategies],
    };
  }

  private function executeStrategy(array $strategy): Response
  {
    $request = Http::timeout(30)->acceptJson();

    if ($strategy['type'] === 'post') {
      return $request->withHeaders($strategy['options']['headers'] ?? [])->post($strategy['url'], $strategy['options']['json'] ?? []);
    }

    return $request->get($strategy['url'], $strategy['options']['query'] ?? []);
  }

  private function shouldRetryWithNextStrategy(Response $response, string $mode): bool
  {
    if ($mode !== 'auto') {
      return false;
    }

    if (in_array($response->status(), self::RETRYABLE_STATUSES, true)) {
      return true;
    }

    $contentType = $response->header('Content-Type') ?? '';

    return ! str_contains($contentType, 'json') && ! $response->successful();
  }

  /** @return list<string> */
  private function legacyBases(array $config): array
  {
    $configured = rtrim($config['base_url'] ?? 'https://edge.ippanel.com/v1', '/');
    $bases = array_unique([
      $configured,
      preg_replace('#/v1$#', '', $configured) ?: 'https://edge.ippanel.com',
      'https://edge.ippanel.com/v1',
      'https://edge.ippanel.com',
    ]);

    return array_values(array_filter($bases));
  }

  private function resolveAuth(array $config): array
  {
    if (! empty($config['api_key'])) {
      $key = $this->normalizeApiKey($config['api_key']);

      return ['token' => $key, 'api_key' => $key];
    }

    if (empty($config['username']) || empty($config['password'])) {
      return ['token' => null, 'api_key' => null, 'error' => 'کلید API یا نام کاربری/رمز عبور IPPanel وارد نشده'];
    }

    $baseUrl = rtrim($config['base_url'] ?? 'https://edge.ippanel.com/v1', '/');

    try {
      $response = Http::timeout(20)
        ->withHeaders(['Content-Type' => 'application/json', 'Accept' => 'application/json'])
        ->post("{$baseUrl}/api/acl/auth/login", [
          'username' => $config['username'],
          'password' => $config['password'],
        ]);

      $body = $response->json();
      $token = $body['data']['token'] ?? null;
      $method = $body['data']['method'] ?? 'login';

      if ($token && ($body['meta']['status'] ?? false)) {
        if ($method !== 'login') {
          return [
            'token' => null,
            'api_key' => null,
            'error' => 'حساب IPPanel احراز هویت دو مرحله‌ای (۲FA) دارد. از کلید API استفاده کنید.',
          ];
        }

        return ['token' => $token, 'api_key' => null];
      }

      return ['token' => null, 'api_key' => null, 'error' => 'ورود IPPanel ناموفق: '.($body['meta']['message'] ?? $response->body())];
    } catch (\Throwable $e) {
      return ['token' => null, 'api_key' => null, 'error' => 'خطا در ورود IPPanel: '.$e->getMessage()];
    }
  }

  private function parseResponse(Response $response): array
  {
    $contentType = $response->header('Content-Type') ?? '';
    $body = str_contains($contentType, 'json') ? ($response->json() ?? []) : [];

    if (empty($body) && ! $response->successful()) {
      return [
        'success' => false,
        'message' => "خطای IPPanel (HTTP {$response->status()}): سرور IPPanel در دسترس نیست. حالت legacy امتحان می‌شود.",
        'details' => ['http_status' => $response->status()],
      ];
    }

    // Edge API format
    if (isset($body['meta'])) {
      $status = $body['meta']['status'] ?? false;
      $message = $body['meta']['message'] ?? 'نامشخص';

      if ($response->successful() && $status) {
        return ['success' => true, 'message' => $message, 'details' => $body['data'] ?? null];
      }

      return ['success' => false, 'message' => $message, 'details' => $body];
    }

    // Legacy formats
    if (isset($body['status']) && ($body['status'] === true || $body['status'] === 1 || $body['status'] === '1')) {
      return ['success' => true, 'message' => $body['message'] ?? 'ارسال شد', 'details' => $body];
    }

    if (isset($body['data']) && is_numeric($body['data'])) {
      return ['success' => true, 'message' => 'ارسال شد', 'details' => $body];
    }

    if ($response->successful() && empty($body)) {
      return ['success' => true, 'message' => 'ارسال شد'];
    }

    return [
      'success' => false,
      'message' => $body['message'] ?? $body['error'] ?? "خطای IPPanel (HTTP {$response->status()})",
      'details' => $body ?: ['http_status' => $response->status()],
    ];
  }

  private function hasCredentials(array $config): bool
  {
    return ! empty($config['api_key']) || (! empty($config['username']) && ! empty($config['password']));
  }

  private function apiBase(array $config): string
  {
    $base = rtrim($config['base_url'] ?? 'https://edge.ippanel.com/v1', '/');

    return str_ends_with($base, '/api') ? $base : $base.'/api';
  }

  private function normalizeApiKey(string $key): string
  {
    $key = trim($key);

    if (preg_match('/^[A-Za-z0-9+\/]+=*$/', $key) && strlen($key) > 40) {
      $decoded = base64_decode($key, true);
      if (is_string($decoded) && $decoded !== '' && preg_match('/^[a-zA-Z0-9\-]+$/', $decoded)) {
        return $decoded;
      }
    }

    return $key;
  }

  private function normalizeSender(string $number): string
  {
    $number = trim($number);

    if (! str_starts_with($number, '+')) {
      $number = str_starts_with($number, '0') ? '+98'.substr($number, 1) : '+'.$number;
    }

    return $number;
  }

  private function toE164(string $mobile): string
  {
    return $this->normalizeSender($mobile);
  }
}
