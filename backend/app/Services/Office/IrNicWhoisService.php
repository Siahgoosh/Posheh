<?php

namespace App\Services\Office;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class IrNicWhoisService
{
    /** @return array{available: bool|null, message: string, raw?: string}> */
    public function checkAvailability(string $domain): array
    {
        $domain = strtolower(trim($domain));
        if (! preg_match('/^[a-z0-9]([a-z0-9-]*[a-z0-9])?\.ir$/', $domain)) {
            return [
                'available' => null,
                'message' => 'فرمت دامنه نامعتبر است. مثال: myoffice.ir',
            ];
        }

        $response = $this->whoisQuery($domain);
        if ($response === null) {
            $response = $this->whoisHttpFallback($domain);
        }

        if ($response === null) {
            return [
                'available' => null,
                'message' => 'اتصال به whois.nic.ir برقرار نشد. در nic.ir دستی بررسی کنید.',
            ];
        }

        return $this->parseWhoisResponse($response);
    }

    /** @return array{available: bool|null, message: string, raw?: string}> */
    private function parseWhoisResponse(string $response): array
    {
        $lower = strtolower($response);

        $availablePatterns = [
            'no matching record',
            'not found',
            'no entries found',
            'no match for',
            'status: available',
            'available for registration',
            'یافت نشد',
            'موجود نیست',
            'no data found',
        ];

        $unavailablePatterns = [
            'domain name:',
            'domain:',
            'holder:',
            'nic-hdl:',
            'nserver:',
            'status: active',
            'status:ok',
            'registered',
            'registrar:',
        ];

        foreach ($availablePatterns as $pattern) {
            if (str_contains($lower, $pattern)) {
                return [
                    'available' => true,
                    'message' => 'این دامنه احتمالاً آزاد است و قابل ثبت می‌باشد.',
                    'raw' => config('app.debug') ? $response : null,
                ];
            }
        }

        foreach ($unavailablePatterns as $pattern) {
            if (str_contains($lower, $pattern)) {
                return [
                    'available' => false,
                    'message' => 'این دامنه قبلاً ثبت شده و در دسترس نیست.',
                    'raw' => config('app.debug') ? $response : null,
                ];
            }
        }

        return [
            'available' => null,
            'message' => 'نتیجه نامشخص — لطفاً در nic.ir هم بررسی کنید.',
            'raw' => config('app.debug') ? $response : null,
        ];
    }

    private function whoisQuery(string $domain): ?string
    {
        $host = 'whois.nic.ir';
        $port = 43;
        $timeout = 12;

        $errno = 0;
        $errstr = '';
        $fp = @fsockopen($host, $port, $errno, $errstr, $timeout);
        if (! $fp) {
            Log::warning('IRNIC whois connect failed', ['errno' => $errno, 'errstr' => $errstr]);

            return null;
        }

        stream_set_timeout($fp, $timeout);
        fwrite($fp, $domain."\r\n");
        $response = '';
        while (! feof($fp)) {
            $chunk = fread($fp, 8192);
            if ($chunk === false) {
                break;
            }
            $response .= $chunk;
        }
        fclose($fp);

        return trim($response) !== '' ? $response : null;
    }

    private function whoisHttpFallback(string $domain): ?string
    {
        try {
            $response = Http::timeout(15)
                ->withHeaders(['User-Agent' => 'Posheh/1.0'])
                ->get('https://www.nic.ir/Whois', ['domain' => $domain]);

            if ($response->successful()) {
                $body = strip_tags($response->body());
                $body = html_entity_decode($body, ENT_QUOTES | ENT_HTML5, 'UTF-8');

                return trim($body) !== '' ? $body : null;
            }
        } catch (\Throwable $e) {
            Log::warning('IRNIC HTTP whois fallback failed', ['domain' => $domain, 'error' => $e->getMessage()]);
        }

        return null;
    }
}
