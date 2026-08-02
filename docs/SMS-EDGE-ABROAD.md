# SMS از سرور خارج از ایران (هلند)

## تست API — اول این را اجرا کن

```bash
docker compose exec app php artisan system:sms-api-test 09170577873
```

این دستور بدون ارسال واقعی، دو مسیر را تست می‌کند:
- **Edge API** (`edge.ippanel.com`) — معمولاً از هلند **502 / timeout**
- **JSPD webservice** (`ippanel.com/services.jspd`, `op=send`) — معمولاً **کار می‌کند**

برای ارسال واقعی:

```bash
docker compose exec app php artisan system:sms-api-test 09170577873 --send
```

## تنظیم `.env` (بعد از تأیید webservice)

```env
SMS_MODE=live
IPPANEL_API_MODE=jspd
IPPANEL_USERNAME=...
IPPANEL_PASSWORD=...
IPPANEL_FROM_NUMBER=+983000505
```

**OTP فقط از webservice** ارسال می‌شود — بدون پترن IPPanel، بدون Edge API، بدون relay ایران.

پیام OTP: `کد تأیید پوشه: 123456`

## فعال‌سازی

```bash
./scripts/force-sms-jspd.sh
docker compose exec app php artisan system:sms-probe 09170577873 --send
```

## حالت log (توسعه)

```bash
docker compose exec app php artisan system:sms-enable --log
# کد OTP: 123456
```

## نکته

پترن و Edge API برای OTP استفاده نمی‌شوند. پیامک‌های دیگر (دعوت، یادآوری) همچنان از webservice یا پترن (در صورت نیاز) می‌روند.
