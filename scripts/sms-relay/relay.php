<?php
/**
 * SMS Relay — deploy on a server INSIDE Iran (or any host that can reach edge.ippanel.com).
 *
 * 1. Copy this file + .env to your Iran VPS (e.g. /var/www/sms-relay/relay.php)
 * 2. Set credentials in .env (see .env.example)
 * 3. On Netherlands server set:
 *      SMS_RELAY_URL=https://your-iran-server.example.com/relay.php
 *      SMS_RELAY_SECRET=same-as-relay-secret
 *
 * Nginx example:
 *   location /relay.php { fastcgi_pass unix:/run/php/php8.2-fpm.sock; ... }
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'POST only']);
    exit;
}

$envFile = __DIR__.'/.env';
if (is_file($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || ! str_contains($line, '=')) {
            continue;
        }
        [$k, $v] = explode('=', $line, 2);
        putenv(trim($k).'='.trim($v, " \t\"'"));
    }
}

$secret = getenv('SMS_RELAY_SECRET') ?: '';
$header = $_SERVER['HTTP_X_SMS_RELAY_SECRET'] ?? '';
if ($secret === '' || ! hash_equals($secret, $header)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit;
}

$body = json_decode(file_get_contents('php://input') ?: '', true);
if (! is_array($body)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON']);
    exit;
}

$apiKey = getenv('IPPANEL_API_KEY') ?: '';
$baseUrl = rtrim(getenv('IPPANEL_BASE_URL') ?: 'https://edge.ippanel.com/v1', '/');
$sendUrl = str_ends_with($baseUrl, '/api') ? "{$baseUrl}/send" : "{$baseUrl}/api/send";

if ($apiKey === '') {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'IPPANEL_API_KEY missing on relay server']);
    exit;
}

$mobile = preg_replace('/\D/', '', (string) ($body['mobile'] ?? ''));
if (str_starts_with($mobile, '0')) {
    $mobile = '+98'.substr($mobile, 1);
} elseif (! str_starts_with($mobile, '98')) {
    $mobile = '+98'.$mobile;
} else {
    $mobile = '+'.$mobile;
}

$type = (string) ($body['type'] ?? 'otp');

if ($type === 'otp') {
    $payload = [
        'sending_type' => 'pattern',
        'from_number' => (string) ($body['from_number'] ?? getenv('IPPANEL_OTP_FROM_NUMBER') ?: '+9810008721297974'),
        'code' => (string) ($body['pattern_code'] ?? getenv('IPPANEL_OTP_PATTERN_CODE') ?: 'qhhly1nai3njev0'),
        'recipients' => [$mobile],
        'params' => ['code' => (string) ($body['code'] ?? '')],
    ];
} else {
    $payload = [
        'sending_type' => 'webservice',
        'from_number' => (string) ($body['from_number'] ?? getenv('IPPANEL_FROM_NUMBER') ?: '+983000505'),
        'message' => (string) ($body['message'] ?? ''),
        'params' => ['recipients' => [$mobile]],
    ];
}

$ch = curl_init($sendUrl);
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: '.$apiKey,
        'Content-Type: application/json',
        'Accept: application/json',
    ],
    CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_TIMEOUT => 30,
]);

$raw = curl_exec($ch);
$httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($raw === false) {
    http_response_code(502);
    echo json_encode(['success' => false, 'message' => 'IPPanel curl error: '.$curlError]);
    exit;
}

$response = json_decode($raw, true) ?? [];
$ok = $httpCode >= 200 && $httpCode < 300 && ($response['meta']['status'] ?? false);

echo json_encode([
    'success' => $ok,
    'message' => $response['meta']['message'] ?? ($ok ? 'ارسال شد' : 'خطای IPPanel'),
    'details' => $response['data'] ?? $response,
], JSON_UNESCAPED_UNICODE);
