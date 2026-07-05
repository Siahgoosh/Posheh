<?php

namespace App\Services\Settings;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Cache;

class SystemSettingsService
{
    private const CACHE_KEY = 'system_settings';

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

    public function set(string $key, mixed $value): void
    {
        SystemSetting::where('key', $key)->update(['value' => is_bool($value) ? ($value ? '1' : '0') : (string) $value]);
        Cache::forget(self::CACHE_KEY);
    }

    public function setMany(array $data): void
    {
        foreach ($data as $key => $value) {
            if ($value === '********' || $value === null || $value === '') {
                continue;
            }
            $this->set($key, $value);
        }
    }

    public function isSmsLive(): bool
    {
        return $this->get('sms_mode', app()->environment('production') ? 'live' : 'log') === 'live';
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
        ];
    }

    public function ippanelConfigFromArray(array $override): array
    {
        return array_merge($this->ippanelConfig(), array_filter($override, fn ($v) => $v !== null && $v !== '' && $v !== '********'));
    }

    public function aqayepardakhtConfig(): array
    {
        return [
            'pin' => $this->get('aqayepardakht_pin'),
            'sandbox' => (bool) $this->get('aqayepardakht_sandbox', true),
        ];
    }

    private function cached(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            return SystemSetting::pluck('value', 'key')->toArray();
        });
    }

    private function formatSetting(SystemSetting $setting, bool $maskSecrets): array
    {
        $value = $setting->value;

        if ($maskSecrets && $setting->is_secret && $value) {
            $value = '********';
        }

        return [
            'key' => $setting->key,
            'value' => $value,
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
