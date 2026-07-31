# SMS Relay — سرور هلند + پنل مکث/پانل‌چی

## تشخیص مشکل

اگر `system:sms-probe` این را نشان می‌دهد:

```
✗ https://edge.ippanel.com/v1 → FAIL: cURL error 28: Operation timed out ... 0 bytes received
```

**علت:** سرور خارج از ایران (مثلاً هلند) به زیرساخت IPPanel **دسترسی شبکه ندارد**.  
این **whitelist IP نیست** — اتصال TCP برقرار می‌شود ولی پاسخ HTTP نمی‌آید.

قبل از فاز ۱، کد OTP فقط در لاگ می‌نوشت و SMS واقعی ارسال نمی‌کرد. بعد از فاز ۱ اتصال واقعی به IPPanel اضافه شد و این محدودیت شبکه مشخص شد.

## راه‌حل: SMS Relay

یک فایل PHP روی **سرور داخل ایران** (VPS ارزان، یا هر ماشینی که به `edge.ippanel.com` دسترسی دارد) اجرا می‌شود. سرور هلند به relay درخواست می‌زند، relay به IPPanel وصل می‌شود.

```
[سرور هلند — پوشه]  --POST-->  [relay.php روی VPS ایران]  --POST-->  edge.ippanel.com
```

### ۱. نصب relay روی سرور ایران

```bash
mkdir -p /var/www/sms-relay
cd /var/www/sms-relay
# کپی از repo:
#   scripts/sms-relay/relay.php
#   scripts/sms-relay/.env.example → .env

cp .env.example .env
nano .env   # IPPANEL_API_KEY و SMS_RELAY_SECRET را پر کنید

# Nginx + PHP-FPM یا:
php -S 0.0.0.0:8080 relay.php
```

`.env` روی relay:

```env
SMS_RELAY_SECRET=یک-رمز-قوی-تصادفی
IPPANEL_API_KEY=کلید-از-پنل-مکث
IPPANEL_OTP_PATTERN_CODE=qhhly1nai3njev0
IPPANEL_OTP_FROM_NUMBER=+9810008721297974
IPPANEL_FROM_NUMBER=+983000505
```

### ۲. تنظیم سرور هلند (پوشه)

در `backend/.env`:

```env
SMS_MODE=live
SMS_RELAY_URL=https://YOUR-IRAN-SERVER.example.com/relay.php
SMS_RELAY_SECRET=همان-رمز-relay
```

سپس:

```bash
./scripts/fix-sms-now.sh
docker compose exec app php artisan system:sms-probe 09170577873 --send
```

### ۳. تست relay مستقیم

```bash
curl -X POST https://YOUR-IRAN-SERVER/relay.php \
  -H "Content-Type: application/json" \
  -H "X-SMS-Relay-Secret: YOUR_SECRET" \
  -d '{"type":"otp","mobile":"09170577873","code":"123456"}'
```

پاسخ موفق: `{"success":true,...}`

## جایگزین: HTTP Proxy

اگر پروکسی SOCKS/HTTP با خروجی ایران دارید:

```env
IPPANEL_HTTP_PROXY=socks5://user:pass@proxy-host:1080
IPPANEL_API_MODE=edge
IPPANEL_API_KEY=...
```

## امنیت

- `SMS_RELAY_SECRET` را قوی انتخاب کنید
- relay را فقط با HTTPS در دسترس بگذارید
- IP سرور هلند را در firewall relay محدود کنید (اختیاری)
