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

        if (! array_key_exists($key, $settings)) {
            return $this->envFallback($key, $default);
        }

        $value = $settings[$key];

        return $this->castValue($key, $value) ?? $default;
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
        return [
            'api_key' => $this->get('ippanel_api_key'),
            'username' => $this->get('ippanel_username'),
            'password' => $this->get('ippanel_password'),
            'from_number' => $this->get('ippanel_from_number'),
            'otp_pattern_code' => $this->get('ippanel_otp_pattern_code'),
            'invite_pattern_code' => $this->get('ippanel_invite_pattern_code'),
            'base_url' => $this->get('ippanel_base_url', 'https://edge.ippanel.com/v1'),
            'api_mode' => $this->get('ippanel_api_mode', 'auto'),
        ];
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
        return match ($key) {
            'ippanel_api_key' => env('IPPANEL_API_KEY', $default),
            'ippanel_username' => env('IPPANEL_USERNAME', $default),
            'ippanel_password' => env('IPPANEL_PASSWORD', $default),
            'ippanel_from_number' => env('IPPANEL_FROM_NUMBER', $default),
            'ippanel_otp_pattern_code' => env('IPPANEL_OTP_PATTERN_CODE', $default),
            'ippanel_invite_pattern_code' => env('IPPANEL_INVITE_PATTERN_CODE', $default),
            'ippanel_base_url' => env('IPPANEL_BASE_URL', $default ?? 'https://edge.ippanel.com/v1'),
            'aqayepardakht_pin' => env('AQAYEPARDAKHT_PIN', $default),
            'aqayepardakht_sandbox' => env('AQAYEPARDAKHT_SANDBOX', $default ?? true),
            'sms_mode' => env('SMS_MODE', $default),
            default => $default,
        };
    }
}
