# SMS از سرور خارج از ایران (Edge API)

سرور پوشه در هلند است. APIهای کلاسیک مکث (`ippanel.com` / JSPD) نیاز به **whitelist IP ایران** دارند و از خارج timeout می‌شوند.

## علت رایج: deploy هر بار SMS را خاموش می‌کند

**قبل از fix:** `deploy.sh` در هر deploy دستور `system:sms-enable --log` اجرا می‌کرد و `sms_mode` در دیتابیس روی `log` می‌ماند — حتی اگر `.env` مقدار `SMS_MODE=live` داشت.

**علائم:**
- API می‌گوید «کد ارسال شد» ولی SMS نمی‌آید
- کد `123456` کار می‌کند
- در پنل مکث چیزی ثبت نمی‌شود

**رفع فوری روی سرور:**

```bash
# 1. کلید API را در .env بگذارید
# IPPANEL_API_KEY=...
# SMS_MODE=live

./scripts/enable-live-sms.sh

# 2. تشخیص کامل
docker compose exec app php artisan system:sms-probe 09170577873 --send
```

## راه‌حل

اگر `system:sms-probe` نشان می‌دهد `edge.ippanel.com` با **timeout و 0 bytes** fail می‌شود، سرور شما به IPPanel دسترسی شبکه ندارد. **راه‌حل:** [SMS Relay](SMS-RELAY.md) روی یک VPS ایران.

برای سروری که مستقیم به Edge دسترسی دارد:

از **Edge API** مکث با **Access Key** استفاده کنید:

- **OTP (کد تأیید):** پترن از طریق `POST /api/send` با `sending_type: pattern`
- **سایر پیامک‌ها** (دعوت، یادآوری، تست): `sending_type: webservice`

مستندات: https://docs.ippanel.com/docs/

## تنظیم `.env`

```env
SMS_MODE=live
IPPANEL_API_MODE=edge
IPPANEL_API_KEY=your-access-key-from-panel
IPPANEL_FROM_NUMBER=+983000505
IPPANEL_OTP_FROM_NUMBER=+9810008721297974
IPPANEL_OTP_PATTERN_CODE=qhhly1nai3njev0
IPPANEL_BASE_URL=https://edge.ippanel.com/v1
```

کلید API: پنل مکث → **Developers** → **Access Keys**

## فعال‌سازی روی سرور

```bash
# در .env مقدار IPPANEL_API_KEY را قرار دهید
./scripts/enable-live-sms.sh

# تست OTP
docker compose exec app php artisan system:sms-test 09170577873 --otp --debug

# تست پیامک عادی
docker compose exec app php artisan system:sms-test 09170577873 --debug
```

## حالت log (توسعه / بدون SMS واقعی)

```bash
docker compose exec app php artisan system:sms-enable --log
# کد OTP: 123456
```

## سرور داخل ایران

اگر سرور IP ایران دارد، می‌توانید `IPPANEL_API_MODE=jspd` و username/password + whitelist IP استفاده کنید.
