# رفع فوری OTP و تور مجازی روی سرور

## وضعیت فعلی

- **حالت تست (`log`)**: کد ثابت `123456` — برای توسعه و تست پنل
- **حالت واقعی (`live`)**: کد ۶ رقمی تصادفی از طریق IPPanel

---

## مشکل ۱: کد OTP نمی‌آید

### راه‌حل فوری (بدون SMS — کد ثابت ۱۲۳۴۵۶)

روی سرور در مسیر `/var/www/posheh`:

```bash
docker compose exec app php artisan system:sms-enable --log
docker compose exec app php artisan cache:clear
```

سپس در سایت «دریافت کد» بزنید و با **`123456`** وارد شوید.

**مهم:** کد `123456` فقط در حالت `log` کار می‌کند.

### فعال‌سازی SMS واقعی (بعد از تست پنل)

```bash
# در backend/.env باید باشد:
# IPPANEL_USERNAME=...
# IPPANEL_PASSWORD=...
# IPPANEL_OTP_PATTERN_CODE=qhhly1nai3njev0

./scripts/enable-live-sms.sh
docker compose exec app php artisan system:sms-test 09170577873 --otp --debug
```

یا دستی:

```bash
docker compose exec app php artisan system:sms-enable --live --from-env
docker compose exec app php artisan cache:clear
```

### نکات فنی

- API فوراً به مرحله کد می‌رود (کد اول ذخیره، SMS از طریق **صف redis**)
- حتماً container `queue` باید running باشد: `docker compose ps queue`
- اگر در پنل SMS چیزی ثبت نمی‌شود: `docker compose logs queue --tail=50`
- لاگ: `docker compose exec app tail -100 storage/logs/laravel.log | grep OTP`

---

## مشکل ۲: تور مجازی

### «یافت نشد»
```bash
docker compose exec app php artisan migrate --force
docker compose exec app php artisan db:seed --class=VirtualTourSeeder --force
```

### روی Loading می‌ماند
تصاویر ۳۶۰ باید از `/demo/sphere.jpg` لود شوند (روی خود دامنه). بعد از deploy:

```bash
curl -I https://posheapp.ir/demo/sphere.jpg
# باید HTTP 200 برگرداند
```

دموی تور: `https://posheapp.ir/tour/demo-apartment-pasdaran`

---

## Deploy

```bash
cd /var/www/posheh
git pull origin cursor/fix-otp-tour-e117
./scripts/deploy.sh cursor/fix-otp-tour-e117
```

برای SMS واقعی بعد از deploy:

```bash
./scripts/enable-live-sms.sh
```

یا در همان deploy:

```bash
SMS_FORCE_LIVE=1 ./scripts/deploy.sh cursor/fix-otp-tour-e117
```
