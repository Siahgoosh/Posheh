<?php

namespace App\Services\Sms;

use App\Services\Settings\SystemSettingsService;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class IpPanelSmsService
{
  private const RETRYABLE_STATUSES = [502, 503, 504, 520, 521, 522, 524];

  private const OTP_PATTERN_CODE = 'qhhly1nai3njev0';

  private const OTP_FROM_NUMBER = '+9810008721297974';

  private const OTP_CONNECT_TIMEOUT = 30;

  private const OTP_REQUEST_TIMEOUT = 60;

  private const OTP_PATTERN_CONNECT_TIMEOUT = 8;

  private const OTP_PATTERN_REQUEST_TIMEOUT = 12;

  public function __construct(
    private readonly SystemSettingsService $settings,
    private readonly SmsRelayClient $relay,
  ) {}

  public function sendOtp(string $mobile, string $code): array
  {
    if (! $this->settings->isSmsLive()) {
      Log::info("OTP SMS [log] to {$mobile}: {$code}");

      return ['success' => true, 'method' => 'log'];
    }

    $config = $this->settings->ippanelConfig();

    if ($this->relay->isConfigured($config)) {
      $patternCode = $this->resolveOtpPatternCode($config);
      $result = $this->relay->sendOtp($mobile, $code, $config);

      return ($result['success'] ?? false)
        ? $this->otpSuccess($mobile, $patternCode, $config, $result)
        : $result;
    }

    $patternCode = $this->resolveOtpPatternCode($config);
    $otpConfig = $this->otpPatternConfig($config);
    $credentialSets = $this->otpCredentialSets($config);
    $mode = $this->resolveApiMode($otpConfig);

    if ($credentialSets === []) {
      Log::warning('OTP SMS credentials missing', ['mobile' => $mobile]);

      return [
        'success' => false,
        'message' => 'تنظیمات OTP ناقص است. IPPANEL_USERNAME و IPPANEL_PASSWORD (یا IPPANEL_API_KEY) را در .env قرار دهید.',
      ];
    }

    $params = ['code' => $code];
    $attempts = [];

    // JSPD mode on NL servers: pattern API returns deny; plain webservice works — send immediately.
    if ($mode === 'jspd') {
      $plainResult = $this->sendOtpPlainWebservice($mobile, $code, $config);
      $attempts[] = $plainResult;
      if ($plainResult['success']) {
        return $this->otpSuccess($mobile, $patternCode, $otpConfig, $plainResult);
      }
    }

    foreach ($credentialSets as $creds) {
      $credConfig = array_merge($otpConfig, [
        'username' => $creds['username'],
        'password' => $creds['password'],
      ]);

      if ($creds['label'] === 'panel' && $mode !== 'jspd') {
        $classicResult = $this->sendOtpClassicPattern($mobile, $patternCode, $params, $credConfig);
        $classicResult['method'] = ($classicResult['method'] ?? 'classic_otp').'_'.$creds['label'];
        $attempts[] = $classicResult;
        if ($classicResult['success']) {
          return $this->otpSuccess($mobile, $patternCode, $otpConfig, $classicResult);
        }
      }

      $jspdResult = $this->sendOtpJspdPattern($mobile, $patternCode, $params, $credConfig);
      $jspdResult['method'] = ($jspdResult['method'] ?? 'jspd_otp').'_'.$creds['label'];
      $attempts[] = $jspdResult;
      if ($jspdResult['success']) {
        return $this->otpSuccess($mobile, $patternCode, $otpConfig, $jspdResult);
      }
      if ($this->isJspdDeny($jspdResult)) {
        break;
      }
    }

    if ($mode !== 'jspd') {
      // Pattern API often returns "deny" from abroad while plain JSPD webservice works.
      $plainResult = $this->sendOtpPlainWebservice($mobile, $code, $config);
      $attempts[] = $plainResult;
      if ($plainResult['success']) {
        return $this->otpSuccess($mobile, $patternCode, $otpConfig, $plainResult);
      }
    }

    if ($mode !== 'jspd' && ! empty($config['api_key'])) {
      $auth = $this->resolveAuth($otpConfig, 'edge', 'ippanel');
      $edgeResult = $this->sendOtpViaEdge($mobile, $patternCode, $params, $otpConfig, $auth);
      $attempts[] = $edgeResult;
      if ($edgeResult['success']) {
        return $this->otpSuccess($mobile, $patternCode, $otpConfig, $edgeResult);
      }
    }

    $last = end($attempts) ?: ['success' => false, 'message' => 'ارسال OTP ناموفق بود'];

    Log::error('OTP send failed on all paths', [
      'mobile' => $mobile,
      'pattern' => $patternCode,
      'from' => $otpConfig['from_number'],
      'credential_labels' => array_column($credentialSets, 'label'),
      'attempts' => array_map(fn ($a) => [
        'method' => $a['method'] ?? null,
        'message' => $a['message'] ?? null,
      ], $attempts),
    ]);

    return [
      'success' => false,
      'message' => $this->otpFailureMessage($attempts),
      'method' => $last['method'] ?? null,
      'details' => $last['details'] ?? null,
      'attempts' => $attempts,
    ];
  }

  /** @param array<string, mixed> $config */
  private function resolveOtpPatternCode(array $config): string
  {
    $patternCode = trim((string) ($config['otp_pattern_code'] ?? ''));

    return $patternCode !== '' ? $patternCode : self::OTP_PATTERN_CODE;
  }

  /** @param array<string, mixed> $config */
  private function resolveApiMode(array $config): string
  {
    return strtolower(trim((string) ($config['api_mode'] ?? 'auto')));
  }

  /** @param array<string, mixed> $config */
  /** @return list<array{label: string, username: string, password: string}> */
  private function otpCredentialSets(array $config): array
  {
    $sets = [];

    $username = trim((string) ($config['username'] ?? ''));
    $password = trim((string) ($config['password'] ?? ''));

    if ($username !== '' && $password !== '' && $password !== '********') {
      $sets[] = ['label' => 'panel', 'username' => $username, 'password' => $password];
    }

    $apiKey = trim((string) ($config['api_key'] ?? ''));
    if ($apiKey !== '') {
      $apiKey = $this->normalizeApiKey($apiKey);
      $sets[] = ['label' => 'apikey', 'username' => $apiKey, 'password' => $apiKey];
    }

    return $sets;
  }

  /** @param array<string, mixed> $config */
  private function hasOtpCredentials(array $config): bool
  {
    return $this->otpCredentialSets($config) !== [];
  }

  /** @param array<string, mixed> $result */
  private function otpSuccess(string $mobile, string $patternCode, array $config, array $result): array
  {
    Log::info('OTP sent', [
      'mobile' => $mobile,
      'method' => $result['method'] ?? null,
      'pattern' => $patternCode,
      'from' => $config['from_number'],
    ]);

    return ['success' => true, 'method' => $result['method'] ?? 'otp_pattern'];
  }

  /** @param list<array<string, mixed>> $attempts */
  private function otpFailureMessage(array $attempts): string
  {
    $messages = array_values(array_filter(array_map(
      fn ($a) => trim((string) ($a['message'] ?? '')),
      $attempts
    )));

    $deny = false;
    foreach ($attempts as $attempt) {
      $raw = (string) ($attempt['details']['raw'] ?? $attempt['details']['code'] ?? '');
      if (strcasecmp($raw, 'deny') === 0) {
        $deny = true;
        break;
      }
    }

    if ($deny) {
      return 'ارسال OTP ناموفق: پترن مکث از این IP رد شد (deny). IP سرور را در پنل مکث whitelist کنید، یا از ارسال plain webservice استفاده کنید.';
    }

    foreach ($messages as $message) {
      if (str_contains($message, 'timed out') || str_contains($message, 'Timeout')) {
        return 'ارسال OTP ناموفق: اتصال به سرور مکث timeout شد. IP سرور را در پنل whitelist کنید یا چند دقیقه بعد دوباره تلاش کنید.';
      }
    }

    return $messages[0] ?? 'ارسال پترن OTP ناموفق بود. تنظیمات پنل مکث را بررسی کنید.';
  }

  private function otpHttp(bool $pattern = true): \Illuminate\Http\Client\PendingRequest
  {
    $connect = $pattern ? self::OTP_PATTERN_CONNECT_TIMEOUT : self::OTP_CONNECT_TIMEOUT;
    $timeout = $pattern ? self::OTP_PATTERN_REQUEST_TIMEOUT : self::OTP_REQUEST_TIMEOUT;

    $request = Http::connectTimeout($connect)
      ->timeout($timeout)
      ->retry($pattern ? 0 : 2, 2000, throw: false);

    $proxy = trim((string) (config('services.ippanel.http_proxy') ?? env('IPPANEL_HTTP_PROXY', '')));
    if ($proxy !== '') {
      $request = $request->withOptions(['proxy' => $proxy]);
    }

    return $request;
  }

  private function smsHttp(int $timeout = 15, int $connect = 5): \Illuminate\Http\Client\PendingRequest
  {
    $options = [
      'curl' => [
        CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      ],
    ];

    $proxy = trim((string) (config('services.ippanel.http_proxy') ?? env('IPPANEL_HTTP_PROXY', '')));
    if ($proxy !== '') {
      $options['proxy'] = $proxy;
    }

    return Http::connectTimeout($connect)->timeout($timeout)->withOptions($options);
  }

  /** @param array<string, mixed> $config */
  private function sendOtpClassicPattern(string $mobile, string $patternCode, array $params, array $config): array
  {
    $code = (string) ($params['code'] ?? '');
    $fromCandidates = $this->otpFromCandidates($config);
    $paramSets = [
      ['code' => $code],
      ['verification-code' => $code],
      'array:'.$code,
    ];

    $lastResult = ['success' => false, 'message' => 'ارسال classic pattern ناموفق بود'];

    foreach ($fromCandidates as $fromNumber) {
      foreach ($paramSets as $paramSet) {
        $payload = is_string($paramSet) && str_starts_with($paramSet, 'array:')
          ? [substr($paramSet, 6)]
          : $paramSet;

        $result = $this->sendClassicPatternRequest($mobile, $patternCode, $payload, array_merge($config, [
          'from_number' => $fromNumber,
        ]));
        $result['method'] = 'classic_otp_f'.preg_replace('/\D/', '', $fromNumber);

        if ($result['success']) {
          return $result;
        }

        $lastResult = $result;
      }
    }

    return $lastResult;
  }

  /** @param array<string, mixed> $config */
  private function sendOtpJspdPattern(string $mobile, string $patternCode, array $params, array $config): array
  {
    $code = (string) ($params['code'] ?? '');
    $fromCandidates = $this->otpFromCandidates($config);
    $jspdMobile = $this->toJspdMobile($mobile);

    $dataInputs = [
      json_encode(['code' => $code], JSON_UNESCAPED_UNICODE),
      json_encode([$code], JSON_UNESCAPED_UNICODE),
      json_encode(['verification-code' => $code], JSON_UNESCAPED_UNICODE),
    ];

    $toVariants = [
      'json_array' => json_encode([$jspdMobile]),
      'plain_9' => $jspdMobile,
      'plain_0' => '0'.$jspdMobile,
    ];

    $formVariants = [];
    foreach ($dataInputs as $dataInput) {
      $formVariants[] = [
        'op' => 'sendPattern',
        'code_pattern' => $patternCode,
        'data_input' => $dataInput,
      ];
      $formVariants[] = [
        'op' => 'sendPattern',
        'pattern_code' => $patternCode,
        'input_data' => $dataInput,
      ];
      $formVariants[] = [
        'op' => 'pattern',
        'p_code' => $patternCode,
        'p_values' => $dataInput,
      ];
    }

    $lastResult = ['success' => false, 'message' => 'ارسال JSPD pattern ناموفق بود'];

    foreach ($fromCandidates as $fromNumber) {
      $from = $this->normalizeSenderForJspd($fromNumber);

      foreach ($toVariants as $toLabel => $toValue) {
        foreach ($formVariants as $index => $variant) {
          try {
            $response = $this->otpHttp()
              ->asForm()
              ->post('https://ippanel.com/services.jspd', [
                'uname' => $config['username'],
                'pass' => $config['password'],
                'from' => $from,
                'to' => $toValue,
                ...$variant,
              ]);

            $result = $this->parseJspdResponse($response);
            $result['method'] = 'jspd_otp_f'.$from.'_'.$toLabel.'_v'.$index;

            if ($result['success']) {
              return $result;
            }

            $lastResult = $result;

            if ($this->isJspdDeny($result)) {
              return $result;
            }
          } catch (\Throwable $e) {
            $lastResult = [
              'success' => false,
              'message' => $e->getMessage(),
              'method' => 'jspd_otp_f'.$from.'_'.$toLabel,
            ];
          }
        }
      }
    }

    return $lastResult;
  }

  /** @param array<string, mixed> $result */
  private function isJspdDeny(array $result): bool
  {
    $code = (string) ($result['details']['code'] ?? $result['details']['raw'] ?? '');

    return strcasecmp($code, 'deny') === 0;
  }

  /** @return list<string> */
  private function otpFromCandidates(array $config): array
  {
    return array_values(array_unique(array_filter([
      trim((string) ($config['otp_from_number'] ?? '')),
      trim((string) ($config['from_number'] ?? '')),
      self::OTP_FROM_NUMBER,
    ])));
  }

  /** @param array<string, mixed>|list<string> $params */
  private function sendClassicPatternRequest(string $mobile, string $patternCode, array $params, array $config): array
  {
    $inputData = array_is_list($params)
      ? json_encode($params, JSON_UNESCAPED_UNICODE)
      : json_encode($params, JSON_UNESCAPED_UNICODE);

    $query = http_build_query([
      'username' => $config['username'],
      'password' => $config['password'],
      'from' => $this->normalizeSenderForJspd($config['from_number']),
      'to' => json_encode([$this->toJspdMobile($mobile)]),
      'input_data' => $inputData,
      'pattern_code' => $patternCode,
    ]);

    try {
      $response = $this->otpHttp()
        ->withBody($inputData, 'application/json')
        ->post('https://ippanel.com/patterns/pattern?'.$query);

      $result = $this->parseClassicPatternResponse($response);
      if ($result['success']) {
        return $result;
      }

      $getResponse = $this->otpHttp()->get('https://ippanel.com/patterns/pattern?'.$query);

      return $this->parseClassicPatternResponse($getResponse);
    } catch (\Throwable $e) {
      return ['success' => false, 'message' => 'خطای classic pattern: '.$e->getMessage()];
    }
  }

  private function parseClassicPatternResponse(Response $response): array
  {
    $raw = trim($response->body());

    if ($raw === '') {
      return [
        'success' => false,
        'message' => 'پاسخ خالی از سرویس پترن مکث',
        'details' => ['http_status' => $response->status()],
      ];
    }

    if (preg_match('/^\d+$/', $raw)) {
      return [
        'success' => true,
        'message' => 'ارسال شد',
        'details' => ['tracking' => $raw, 'code' => '0'],
      ];
    }

    $body = $this->decodeClassicPatternBody($raw);

    if (! is_array($body) || count($body) < 2) {
      return [
        'success' => false,
        'message' => 'خطای پترن مکث: '.$raw,
        'details' => [
          'raw' => mb_substr($raw, 0, 300),
          'http_status' => $response->status(),
        ],
      ];
    }

    $code = (string) $body[0];
    $message = (string) $body[1];

    if (in_array($code, ['0', '1'], true)) {
      return [
        'success' => true,
        'message' => $message ?: 'ارسال شد',
        'details' => ['tracking' => $message, 'code' => $code],
      ];
    }

    return [
      'success' => false,
      'message' => "خطای پترن مکث ({$code}): {$message}",
      'details' => ['code' => $code, 'raw' => $response->body()],
    ];
  }

  /** @return mixed */
  private function decodeClassicPatternBody(string $raw)
  {
    $raw = trim($raw);
    if ($raw === '') {
      return null;
    }

    $decoded = json_decode($raw, true);
    if (json_last_error() === JSON_ERROR_NONE) {
      if (is_string($decoded)) {
        $decoded = json_decode($decoded, true);
      }

      return $decoded;
    }

    if (preg_match('/\[[^\]]+\]/', $raw, $matches)) {
      $decoded = json_decode($matches[0], true);
      if (is_array($decoded)) {
        return $decoded;
      }
    }

    return null;
  }

  /** @param array<string, mixed> $config */
  private function sendOtpViaEdge(string $mobile, string $patternCode, array $params, array $config, array $auth): array
  {
    $authHeaders = $this->otpEdgeAuthHeaders($config, $auth);
    if ($authHeaders === []) {
      return ['success' => false, 'message' => 'کلید API یا نام کاربری/رمز مکث برای Edge API لازم است'];
    }

    $fromCandidates = array_values(array_unique(array_filter([
      trim((string) ($config['otp_from_number'] ?? '')),
      trim((string) ($config['from_number'] ?? '')),
      self::OTP_FROM_NUMBER,
    ])));

    $paramVariants = [
      $params,
      ['code' => (string) ($params['code'] ?? '')],
      ['verification-code' => (string) ($params['code'] ?? '')],
    ];

    $lastResult = ['success' => false, 'message' => 'ارسال Edge pattern ناموفق بود'];

    foreach ($fromCandidates as $fromNumber) {
      $configWithFrom = array_merge($config, ['from_number' => $fromNumber]);

      foreach ($paramVariants as $paramSet) {
        foreach ($authHeaders as $label => $headers) {
          $result = $this->sendPatternEdgeRequest($mobile, $patternCode, $paramSet, $configWithFrom, $headers);
          $result['method'] = 'edge_otp_'.$label.'_'.preg_replace('/\D/', '', $fromNumber);

          if ($result['success']) {
            return $result;
          }

          $lastResult = $result;

          Log::warning('OTP Edge attempt failed', [
            'mobile' => $mobile,
            'method' => $result['method'],
            'message' => $result['message'] ?? null,
            'from' => $fromNumber,
          ]);
        }
      }
    }

    return $lastResult;
  }

  /** @return array<string, array<string, string>> */
  private function otpEdgeAuthHeaders(array $config, array $auth): array
  {
    $headers = [];

    $apiKey = trim((string) ($config['api_key'] ?? $auth['api_key'] ?? ''));
    if ($apiKey !== '') {
      $apiKey = $this->normalizeApiKey($apiKey);
      // Official SDK + docs: Authorization: {api_key} (bare token)
      $headers['api_key'] = ['Authorization' => $apiKey];
      $headers['access_key'] = ['Authorization' => 'AccessKey '.$apiKey];
      $headers['apikey_header'] = ['ApiKey' => $apiKey, 'Authorization' => $apiKey];
    }

    $token = trim((string) ($auth['token'] ?? ''));
    if ($token !== '' && $token !== $apiKey) {
      $headers['login_token'] = ['Authorization' => $token];
      $headers['bearer_token'] = ['Authorization' => 'Bearer '.$token];
    }

    $loginToken = $this->loginEdgeToken($config);
    if ($loginToken !== null && $loginToken !== $apiKey && $loginToken !== $token) {
      $headers['edge_login'] = ['Authorization' => $loginToken];
    }

    return $headers;
  }

  /** @param array<string, mixed> $config */
  private function loginEdgeToken(array $config): ?string
  {
    if (empty($config['username']) || empty($config['password'])) {
      return null;
    }

    $baseUrl = rtrim($config['base_url'] ?? 'https://edge.ippanel.com/v1', '/');

    try {
      $response = $this->otpHttp()
        ->withHeaders(['Content-Type' => 'application/json', 'Accept' => 'application/json'])
        ->post("{$baseUrl}/api/acl/auth/login", [
          'username' => $config['username'],
          'password' => $config['password'],
        ]);

      $body = $response->json();
      $token = $body['data']['token'] ?? null;
      $method = $body['data']['method'] ?? 'login';

      if ($token && ($body['meta']['status'] ?? false) && $method === 'login') {
        return (string) $token;
      }

      Log::warning('Edge login for OTP failed', [
        'status' => $body['meta']['status'] ?? false,
        'method' => $method,
        'message' => $body['meta']['message'] ?? $response->body(),
      ]);
    } catch (\Throwable $e) {
      Log::warning('Edge login exception for OTP', ['error' => $e->getMessage()]);
    }

    return null;
  }

  /** @param array<string, string> $headers */
  private function sendPatternEdgeRequest(string $mobile, string $patternCode, array $params, array $config, array $headers): array
  {
    $payload = [
      'sending_type' => 'pattern',
      'from_number' => $this->normalizeSender($config['from_number']),
      'code' => $patternCode,
      'recipients' => [$this->toE164($mobile)],
      'params' => $params,
    ];

    try {
      $response = $this->otpHttp()
        ->withHeaders(array_merge([
          'Content-Type' => 'application/json',
          'Accept' => 'application/json',
        ], $headers))
        ->post($this->apiBase($config).'/send', $payload);

      return $this->parseResponse($response);
    } catch (\Throwable $e) {
      return ['success' => false, 'message' => 'خطای Edge pattern: '.$e->getMessage()];
    }
  }

  /** @param array<string, mixed> $config */
  private function otpPatternConfig(array $config): array
  {
    if (empty($config['otp_pattern_code'])) {
      $config['otp_pattern_code'] = self::OTP_PATTERN_CODE;
    }

    $otpFrom = trim((string) ($config['otp_from_number'] ?? ''));
    $generalFrom = trim((string) ($config['from_number'] ?? ''));

    if ($otpFrom !== '') {
      $config['from_number'] = $otpFrom;
    } elseif ($generalFrom !== '') {
      $config['from_number'] = $generalFrom;
    } else {
      $config['from_number'] = self::OTP_FROM_NUMBER;
    }

    return $config;
  }

  /** Plain SMS fallback when pattern/JSPD deny but webservice send works (common on NL servers). */
  /** @param array<string, mixed> $config */
  private function sendOtpPlainWebservice(string $mobile, string $code, array $config): array
  {
    $message = "کد تأیید پوشه: {$code}";

    $plainConfig = $config;
    $generalFrom = trim((string) ($config['from_number'] ?? ''));
    if ($generalFrom !== '') {
      $plainConfig['from_number'] = $generalFrom;
    }

    $result = $this->sendWebservice($mobile, $message, $plainConfig, forceLive: true);
    $result['method'] = 'otp_plain_'.($result['method'] ?? 'webservice');

    if ($result['success']) {
      Log::info('OTP sent via plain webservice fallback', [
        'mobile' => $mobile,
        'method' => $result['method'],
      ]);
    }

    return $result;
  }

  public function sendPlain(string $mobile, string $message): array
  {
    $config = $this->settings->ippanelConfig();

    return $this->sendWebservice($mobile, $message, $config);
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
      $patternResult = $this->sendPattern($mobile, $config['invite_pattern_code'], ['office' => $officeName], $config);
      if ($patternResult['success']) {
        return true;
      }

      Log::warning('Invite pattern failed, falling back to webservice', [
        'mobile' => $mobile,
        'message' => $patternResult['message'] ?? null,
      ]);
    }

    return $this->sendWebservice($mobile, $message, $config, forceLive: true)['success'];
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

    if ($this->relay->isConfigured($config)) {
      $result = $this->relay->sendPlain($mobile, $message, $config);
      $result['method'] = 'sms_relay';

      return $result;
    }

    $provider = $config['sms_provider'] ?? $this->settings->get('sms_provider', 'maxsms');
    $mode = $this->resolveApiMode($config);
    $auth = $this->resolveAuth($config, $mode, $provider);

    if (! $this->hasSendCredentials($auth, $config, $mode, $provider)) {
      return ['success' => false, 'message' => $auth['error'] ?? 'خطا در احراز هویت IPPanel'];
    }
    $strategies = $this->webserviceStrategies($config, $mobile, $message, $auth, $mode, $provider);
    $lastResult = ['success' => false, 'message' => 'هیچ روش ارسالی موفق نشد'];

    foreach ($strategies as $strategy) {
      try {
        $response = $this->executeStrategy($strategy);
        $result = $strategy['type'] === 'jspd'
          ? $this->parseJspdResponse($response)
          : $this->parseResponse($response);
        $result['method'] = $strategy['name'];

        if ($result['success']) {
          Log::info('IPPanel SMS sent', ['method' => $strategy['name'], 'mobile' => $mobile]);

          return $result;
        }

        $lastResult = $result;

        if (! $this->shouldRetryWithNextStrategy($response, $mode, $strategy['type'] ?? '')) {
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

  private function sendPattern(string $mobile, string $patternCode, array $params, array $config, bool $otpMode = false): array
  {
    $provider = $config['sms_provider'] ?? $this->settings->get('sms_provider', 'maxsms');
    $mode = $this->resolveApiMode($config);
    $auth = $this->resolveAuth($config, $mode, $provider);

    if (! $this->hasSendCredentials($auth, $config, $mode, $provider)) {
      return ['success' => false, 'message' => $auth['error'] ?? 'خطا در احراز هویت IPPanel'];
    }

    $lastResult = ['success' => false, 'message' => 'ارسال پترن ناموفق بود'];

    if ($mode === 'edge') {
      return $this->sendPatternEdge($mobile, $patternCode, $params, $config, $auth);
    }

    if ($provider === 'maxsms' || $mode === 'jspd' || $mode === 'auto' || $mode === 'legacy') {
      $jspdResult = $this->sendPatternJspd($mobile, $patternCode, $params, $config, $auth, $otpMode);
      if ($jspdResult['success']) {
        return $jspdResult;
      }
      $lastResult = $jspdResult;

      $classicResult = $this->sendPatternClassic($mobile, $patternCode, $params, $config);
      if ($classicResult['success']) {
        return $classicResult;
      }
      $lastResult = $classicResult;

      if ($mode === 'jspd' || $mode === 'legacy') {
        return $lastResult;
      }
    }

    if ($mode === 'edge' || $mode === 'auto' || $provider === 'ippanel') {
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

        $lastResult = $result;
      } catch (\Throwable $e) {
        Log::error('IPPanel pattern exception', ['error' => $e->getMessage()]);
      }
    }

    $legacyResult = $this->sendPatternLegacy($mobile, $patternCode, $params, $config, $auth);

    return $legacyResult['success'] ? $legacyResult : $lastResult;
  }

  /** @param array<string, mixed> $config */
  private function sendPatternEdge(string $mobile, string $patternCode, array $params, array $config, array $auth): array
  {
    $authHeaders = $this->otpEdgeAuthHeaders($config, $auth);
    if ($authHeaders === []) {
      return ['success' => false, 'message' => 'توکن Edge API در دسترس نیست'];
    }

    $lastResult = ['success' => false, 'message' => 'ارسال Edge pattern ناموفق بود'];

    foreach ($authHeaders as $label => $headers) {
      $result = $this->sendPatternEdgeRequest($mobile, $patternCode, $params, $config, $headers);
      $result['method'] = 'edge_pattern_'.$label;

      if ($result['success']) {
        return $result;
      }

      $lastResult = $result;
    }

    return $lastResult;
  }

  private function sendPatternJspd(string $mobile, string $patternCode, array $params, array $config, array $auth, bool $otpMode = false): array
  {
    $from = $this->normalizeSenderForJspd($config['from_number']);
    $recipients = json_encode([$this->toJspdMobile($mobile)]);

    $credentialSets = [];
    if (! empty($config['username']) && ! empty($config['password'])) {
      $credentialSets[] = ['uname' => $config['username'], 'pass' => $config['password'], 'label' => 'user'];
    }
    if (! empty($auth['api_key'])) {
      $credentialSets[] = ['uname' => $auth['api_key'], 'pass' => $auth['api_key'], 'label' => 'apikey'];
    }

    $lastResult = ['success' => false, 'message' => 'ارسال پترن JSPD ناموفق بود'];

    $pValueVariants = $this->patternValueVariants($params, $otpMode);

    foreach ($credentialSets as $creds) {
      foreach ($pValueVariants as $index => $pValues) {
        $formVariants = $otpMode
          ? [
            ['op' => 'pattern', 'p_code' => $patternCode, 'p_values' => $pValues],
            ['op' => 'sendPattern', 'pattern_code' => $patternCode, 'input_data' => $pValues],
          ]
          : [
            ['op' => 'pattern', 'p_code' => $patternCode, 'p_values' => $pValues],
            ['op' => 'sendPattern', 'pattern_code' => $patternCode, 'input_data' => $pValues],
          ];

        foreach ($formVariants as $variant) {
          try {
            $response = Http::timeout(20)->asForm()->post('https://ippanel.com/services.jspd', [
              'uname' => $creds['uname'],
              'pass' => $creds['pass'],
              'from' => $from,
              'to' => $recipients,
              ...$variant,
            ]);

            $result = $this->parseJspdResponse($response);
            $result['method'] = 'jspd_pattern_'.$creds['label'].'_'.$variant['op'].'_v'.$index;

            if ($result['success']) {
              Log::info('IPPanel pattern sent via JSPD', [
                'method' => $result['method'],
                'mobile' => $mobile,
                'p_values' => $pValues,
                'otp_mode' => $otpMode,
              ]);

              return $result;
            }

            $lastResult = $result;
          } catch (\Throwable $e) {
            $lastResult = ['success' => false, 'message' => $e->getMessage(), 'method' => 'jspd_pattern'];

            Log::warning('IPPanel JSPD pattern exception', [
              'mobile' => $mobile,
              'otp_mode' => $otpMode,
              'error' => $e->getMessage(),
            ]);
          }
        }
      }
    }

    return $lastResult;
  }

  /** @return list<string> */
  private function patternValueVariants(array $params, bool $otpMode = false): array
  {
    $code = (string) ($params['code'] ?? $params['otp'] ?? $params['verification-code'] ?? array_values($params)[0] ?? '');

    if ($otpMode && $code !== '') {
      return [
        json_encode([$code], JSON_UNESCAPED_UNICODE),
        json_encode(['code' => $code], JSON_UNESCAPED_UNICODE),
      ];
    }

    $variants = [];

    if ($code !== '') {
      $variants[] = json_encode([$code]);
      $variants[] = json_encode(['code' => $code]);
      $variants[] = json_encode(['verification-code' => $code]);
      $variants[] = json_encode(['otp' => $code]);
    }

    $variants[] = json_encode(array_values($params), JSON_UNESCAPED_UNICODE);
    $variants[] = json_encode($params, JSON_UNESCAPED_UNICODE);

    return array_values(array_unique(array_filter($variants)));
  }

  private function sendPatternClassic(string $mobile, string $patternCode, array $params, array $config): array
  {
    if (empty($config['username']) || empty($config['password'])) {
      return ['success' => false, 'message' => 'نام کاربری/رمز برای پترن کلاسیک لازم است'];
    }

    $result = $this->sendClassicPatternRequest($mobile, $patternCode, $params, $config);
    $result['method'] = 'classic_pattern_url';

    return $result;
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
  private function webserviceStrategies(array $config, string $mobile, string $message, array $auth, string $mode, string $provider = 'maxsms'): array
  {
    $token = $auth['token'] ?? $auth['api_key'];
    $from = $this->normalizeSender($config['from_number']);
    $to = $this->toE164($mobile);
    $edgeBase = $this->apiBase($config);

    $jspdStrategies = $this->jspdStrategies($config, $mobile, $message, $auth);

    $edgeHeaders = $this->edgeAuthHeaders($token);
    $edgePost = [
      'name' => 'edge_post',
      'type' => 'post',
      'url' => "{$edgeBase}/send",
      'options' => [
        'headers' => array_merge([
          'Content-Type' => 'application/json',
          'Accept' => 'application/json',
        ], $edgeHeaders),
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

    return match (true) {
      $mode === 'jspd' => $jspdStrategies,
      $mode === 'edge' => [$edgePost],
      $mode === 'legacy' => $legacyStrategies,
      $provider === 'maxsms' => [...$jspdStrategies, $edgePost, ...$legacyStrategies],
      default => [$edgePost, ...$legacyStrategies, ...$jspdStrategies],
    };
  }

  /** @return list<array{name: string, type: string, url: string, options: array}> */
  private function jspdStrategies(array $config, string $mobile, string $message, array $auth): array
  {
    $strategies = [];
    $recipients = json_encode([$this->toJspdMobile($mobile)]);
    $from = $this->normalizeSenderForJspd($config['from_number']);

    $credentialSets = [];
    if (! empty($config['username']) && ! empty($config['password'])) {
      $credentialSets[] = ['uname' => $config['username'], 'pass' => $config['password'], 'label' => 'user'];
    }
    if (! empty($auth['api_key'])) {
      $credentialSets[] = ['uname' => $auth['api_key'], 'pass' => $auth['api_key'], 'label' => 'apikey'];
    }

    foreach ($credentialSets as $creds) {
      $strategies[] = [
        'name' => 'jspd_'.$creds['label'],
        'type' => 'jspd',
        'url' => 'https://ippanel.com/services.jspd',
        'options' => [
          'form' => [
            'uname' => $creds['uname'],
            'pass' => $creds['pass'],
            'from' => $from,
            'message' => $message,
            'to' => $recipients,
            'op' => 'send',
          ],
        ],
      ];
    }

    return $strategies;
  }

  private function executeStrategy(array $strategy): Response
  {
    $request = Http::timeout(30)->acceptJson();

    if ($strategy['type'] === 'jspd') {
      return Http::timeout(30)
        ->asForm()
        ->post($strategy['url'], $strategy['options']['form'] ?? []);
    }

    if ($strategy['type'] === 'post') {
      return $request->withHeaders($strategy['options']['headers'] ?? [])->post($strategy['url'], $strategy['options']['json'] ?? []);
    }

    return $request->get($strategy['url'], $strategy['options']['query'] ?? []);
  }

  private function parseJspdResponse(Response $response): array
  {
    $raw = trim($response->body());

    if (strcasecmp($raw, 'deny') === 0) {
      return [
        'success' => false,
        'message' => 'دسترسی JSPD رد شد (deny). IP سرور را در پنل مکث whitelist کنید یا از Edge API با کلید API استفاده کنید.',
        'details' => [
          'raw' => 'deny',
          'http_status' => $response->status(),
          'code' => 'deny',
        ],
      ];
    }

    $body = $this->decodeJspdBody($raw);

    if (! is_array($body) || count($body) < 2) {
      return [
        'success' => false,
        'message' => 'پاسخ نامعتبر از سرویس مکث/آی‌پی‌پنل',
        'details' => [
          'raw' => mb_substr($raw, 0, 300),
          'http_status' => $response->status(),
        ],
      ];
    }

    $code = (string) $body[0];
    $message = (string) $body[1];

    if (in_array($code, ['0', '1'], true)) {
      return ['success' => true, 'message' => $message ?: 'ارسال شد', 'details' => ['tracking' => $message]];
    }

    return ['success' => false, 'message' => "خطای مکث/آی‌پی‌پنل ({$code}): {$message}", 'details' => ['code' => $code]];
  }

  /** @return mixed */
  private function decodeJspdBody(string $raw)
  {
    $raw = trim($raw);
    if ($raw === '') {
      return null;
    }

    $decoded = json_decode($raw, true);
    if (json_last_error() === JSON_ERROR_NONE) {
      if (is_string($decoded)) {
        $decoded = json_decode($decoded, true);
      }

      return $decoded;
    }

    if (preg_match('/\[[^\]]+\]/', $raw, $matches)) {
      $decoded = json_decode($matches[0], true);
      if (is_array($decoded)) {
        return $decoded;
      }
    }

    return null;
  }

  private function normalizeSenderForJspd(string $number): string
  {
    $number = preg_replace('/\D/', '', $number);
    if (str_starts_with($number, '98')) {
      return substr($number, 2);
    }

    return ltrim($number, '0');
  }

  private function toJspdMobile(string $mobile): string
  {
    $mobile = preg_replace('/\D/', '', $mobile);
    if (str_starts_with($mobile, '0')) {
      $mobile = substr($mobile, 1);
    }
    if (str_starts_with($mobile, '98')) {
      $mobile = substr($mobile, 2);
    }

    return $mobile;
  }

  private function shouldRetryWithNextStrategy(Response $response, string $mode, string $strategyType = ''): bool
  {
    if ($mode === 'jspd' || $mode === 'edge' || $mode === 'legacy') {
      return false;
    }

    if ($strategyType === 'jspd' && ! $response->successful()) {
      return true;
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

  private function hasSendCredentials(array $auth, array $config, string $mode, string $provider): bool
  {
    if ($auth['token'] || $auth['api_key']) {
      return true;
    }

    if ($mode === 'edge') {
      return false;
    }

    if (! empty($auth['panel_auth'])) {
      return true;
    }

    if (($mode === 'jspd' || $mode === 'legacy' || $provider === 'maxsms')
      && ! empty($config['username']) && ! empty($config['password'])) {
      return true;
    }

    return false;
  }

  /** @return array<string, string> */
  private function edgeAuthHeaders(?string $token): array
  {
    if ($token === null || $token === '') {
      return [];
    }

    if (str_starts_with($token, 'AccessKey ')) {
      return ['Authorization' => $token];
    }

    // IPPanel Edge docs + Python SDK: bare API key in Authorization header
    return ['Authorization' => $token];
  }

  private function resolveAuth(array $config, string $mode = 'auto', string $provider = 'maxsms'): array
  {
    if (! empty($config['api_key'])) {
      $key = $this->normalizeApiKey($config['api_key']);

      return ['token' => $key, 'api_key' => $key];
    }

    if (empty($config['username']) || empty($config['password'])) {
      return ['token' => null, 'api_key' => null, 'error' => 'کلید API یا نام کاربری/رمز عبور IPPanel وارد نشده'];
    }

    if ($mode === 'jspd' || $mode === 'legacy' || $provider === 'maxsms') {
      return [
        'token' => null,
        'api_key' => null,
        'panel_auth' => true,
        'username' => $config['username'],
        'password' => $config['password'],
      ];
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
      $status = $response->status();
      $hint = $status === 502
        ? 'سرور Edge مکث در دسترس نیست (HTTP 502). اتصال شبکه یا وضعیت سرویس مکث را بررسی کنید.'
        : "سرور IPPanel در دسترس نیست (HTTP {$status}).";

      return [
        'success' => false,
        'message' => "خطای IPPanel: {$hint}",
        'details' => ['http_status' => $status, 'raw' => mb_substr(trim($response->body()), 0, 200)],
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

    if (str_starts_with($key, 'base64:')) {
      $decoded = base64_decode(substr($key, 7), true);
      if (is_string($decoded) && $decoded !== '') {
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
