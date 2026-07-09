<?php

namespace App\Services\Settings;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SystemSettingsService
{
    private const CACHE_KEY = 'system_settings';

    private const SECRET_PLACEHOLDER = '********';

    public function all(bool $maskSecrets = true): array
    {
        return SystemSetting::orderBy('group')->orderBy('key')->get()
            ->map(fn (SystemSetting $s) => $this->formatSetting($s, $maskSecrets))
            ->groupBy('group')
            ->toArray();
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $settings = $this->cached();

        $value = null;
        if (array_key_exists($key, $settings)) {
            $value = $settings[$key];
        }

        if ($this->isMaskedSecret($value)) {
            $value = null;
        }

        if ($value === null || $value === '') {
            $envValue = $this->envFallback($key, null);
            if ($envValue !== null && $envValue !== '' && ! $this->isMaskedSecret($envValue)) {
                return $this->castValue($key, is_bool($envValue) ? ($envValue ? '1' : '0') : (string) $envValue);
            }

            return $default;
        }

        return $this->castValue($key, $value) ?? $default;
    }

    private function isMaskedSecret(mixed $value): bool
    {
        if (! is_string($value)) {
            return false;
        }

        $value = trim($value);

        return $value === self::SECRET_PLACEHOLDER || $value === '********';
    }

    public function hasValue(string $key): bool
    {
        $value = $this->cached()[$key] ?? null;

        return $value !== null && $value !== '';
    }

    public function set(string $key, mixed $value): bool
    {
        $setting = SystemSetting::where('key', $key)->first();

        if (! $setting) {
            Log::warning('Attempted to set unknown system setting', ['key' => $key]);

            return false;
        }

        $stored = is_bool($value) ? ($value ? '1' : '0') : (string) $value;

        $setting->update(['value' => $stored]);
        Cache::forget(self::CACHE_KEY);

        return true;
    }

    /**
     * @return array{saved: string[], skipped: string[], errors: string[]}
     */
    public function setMany(array $data): array
    {
        $saved = [];
        $skipped = [];
        $errors = [];

        foreach ($data as $key => $value) {
            if ($this->shouldSkipValue($key, $value)) {
                $skipped[] = $key;

                continue;
            }

            if ($this->set($key, $value)) {
                $saved[] = $key;
            } else {
                $errors[] = $key;
            }
        }

        return compact('saved', 'skipped', 'errors');
    }

    public function isSmsLive(): bool
    {
        $envMode = strtolower(trim((string) config('services.ippanel.sms_mode', env('SMS_MODE', ''))));
        if ($envMode === 'live') {
            return true;
        }
        if ($envMode === 'log') {
            return false;
        }

        if ($this->get('sms_mode') === 'live') {
            return true;
        }

        // Production: credentials configured → send real SMS even if DB still says log
        if (app()->environment('production')) {
            $config = $this->ippanelConfig();
            if ($this->hasIppanelCredentials($config) && ! empty($config['from_number'])) {
                return true;
            }
        }

        return $this->get('sms_mode', app()->environment('production') ? 'live' : 'log') === 'live';
    }

    public function smsStatus(): array
    {
        $config = $this->ippanelConfig();
        $hasCredentials = $this->hasIppanelCredentials($config);

        return [
            'sms_mode' => (string) $this->get('sms_mode', 'log'),
            'is_live' => $this->isSmsLive(),
            'has_api_key' => $this->hasValue('ippanel_api_key'),
            'has_username' => $this->hasValue('ippanel_username'),
            'has_password' => $this->hasValue('ippanel_password'),
            'has_from_number' => ! empty($config['from_number']),
            'has_credentials' => $hasCredentials,
            'is_ready' => $hasCredentials && ! empty($config['from_number']),
        ];
    }

    public function ippanelConfig(): array
    {
        $config = [
            'api_key' => $this->get('ippanel_api_key'),
            'username' => $this->get('ippanel_username'),
            'password' => $this->get('ippanel_password'),
            'from_number' => $this->get('ippanel_from_number'),
            'otp_from_number' => $this->get('ippanel_otp_from_number'),
            'otp_pattern_code' => $this->get('ippanel_otp_pattern_code'),
            'invite_pattern_code' => $this->get('ippanel_invite_pattern_code'),
            'base_url' => $this->get('ippanel_base_url', 'https://edge.ippanel.com/v1'),
            'api_mode' => $this->get('ippanel_api_mode', 'auto'),
            'sms_provider' => $this->normalizeSmsProvider((string) $this->get('sms_provider', 'maxsms')),
        ];

        return $this->mergeIppanelEnvConfig($config);
    }

    /** @param array<string, mixed> $config */
    private function mergeIppanelEnvConfig(array $config): array
    {
        $envConfig = config('services.ippanel', []);

        foreach ([
            'api_key', 'username', 'password', 'from_number', 'otp_from_number',
            'otp_pattern_code', 'invite_pattern_code', 'base_url', 'api_mode',
        ] as $key) {
            $value = $envConfig[$key] ?? null;
            if ($value !== null && $value !== '' && ! $this->isMaskedSecret($value)) {
                $config[$key] = $value;
            }
        }

        if (empty($config['otp_pattern_code'])) {
            $config['otp_pattern_code'] = 'qhhly1nai3njev0';
        }

        if (empty($config['otp_from_number'])) {
            $config['otp_from_number'] = '+9810008721297974';
        }

        return $config;
    }

    private function normalizeSmsProvider(string $provider): string
    {
        $provider = trim($provider);

        if ($provider === '' || str_contains($provider, '=')) {
            return 'maxsms';
        }

        return $provider;
    }

    public function ippanelConfigFromArray(array $override): array
    {
        $filtered = array_filter(
            $override,
            fn ($value) => $value !== null && $value !== '' && $value !== self::SECRET_PLACEHOLDER
        );

        return array_merge($this->ippanelConfig(), $filtered);
    }

    public function aqayepardakhtConfig(): array
    {
        return [
            'pin' => $this->get('aqayepardakht_pin'),
            'sandbox' => (bool) $this->get('aqayepardakht_sandbox', true),
        ];
    }

    public function zarinpalConfig(): array
    {
        return [
            'merchant_id' => $this->get('zarinpal_merchant_id') ?: env('ZARINPAL_MERCHANT_ID'),
            'sandbox' => (bool) $this->get('zarinpal_sandbox', env('ZARINPAL_SANDBOX', true)),
        ];
    }

    private function shouldSkipValue(string $key, mixed $value): bool
    {
        if ($value === self::SECRET_PLACEHOLDER || $value === null) {
            return true;
        }

        if ($value === '') {
            $setting = SystemSetting::where('key', $key)->first();

            return $setting?->is_secret ?? true;
        }

        return false;
    }

    private function hasIppanelCredentials(array $config): bool
    {
        return ! empty($config['api_key'])
            || (! empty($config['username']) && ! empty($config['password']));
    }

    private function cached(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            return SystemSetting::pluck('value', 'key')->toArray();
        });
    }

    private function formatSetting(SystemSetting $setting, bool $maskSecrets): array
    {
        $hasValue = $setting->value !== null && $setting->value !== '';
        $value = $setting->value ?? '';

        if ($maskSecrets && $setting->is_secret && $hasValue) {
            $value = self::SECRET_PLACEHOLDER;
        }

        return [
            'key' => $setting->key,
            'value' => $value,
            'has_value' => $hasValue,
            'label' => $setting->label,
            'type' => $setting->type,
            'is_secret' => $setting->is_secret,
        ];
    }

    private function castValue(string $key, ?string $value): mixed
    {
        if ($value === null) {
            return null;
        }

        if (str_ends_with($key, '_sandbox') || str_starts_with($key, 'enable_')) {
            return in_array($value, ['1', 'true', 'yes'], true);
        }

        return $value;
    }

    private function envFallback(string $key, mixed $default): mixed
    {
        $ippanel = config('services.ippanel', []);

        return match ($key) {
            'ippanel_api_key' => $ippanel['api_key'] ?? $default,
            'ippanel_username' => $ippanel['username'] ?? $default,
            'ippanel_password' => $ippanel['password'] ?? $default,
            'ippanel_from_number' => $ippanel['from_number'] ?? $default,
            'ippanel_otp_from_number' => $ippanel['otp_from_number'] ?? $default,
            'ippanel_otp_pattern_code' => $ippanel['otp_pattern_code'] ?? $default,
            'ippanel_invite_pattern_code' => $ippanel['invite_pattern_code'] ?? $default,
            'ippanel_base_url' => $ippanel['base_url'] ?? $default ?? 'https://edge.ippanel.com/v1',
            'ippanel_api_mode' => $ippanel['api_mode'] ?? $default,
            'aqayepardakht_pin' => env('AQAYEPARDAKHT_PIN', $default),
            'aqayepardakht_sandbox' => env('AQAYEPARDAKHT_SANDBOX', $default ?? true),
            'zarinpal_merchant_id' => env('ZARINPAL_MERCHANT_ID', $default),
            'zarinpal_sandbox' => env('ZARINPAL_SANDBOX', $default ?? true),
            'sms_mode' => env('SMS_MODE', $default),
            'sms_provider' => env('SMS_PROVIDER', $default),
            default => $default,
        };
    }
}
