# رفع فوری OTP و تور مجازی روی سرور

## مشکل ۱: کد OTP نمی‌آید

### راه‌حل فوری (بدون SMS — کد ثابت ۱۲۳۴۵۶)

روی سرور در مسیر `/var/www/posheh`:

```bash
docker compose exec app php artisan tinker --execute="app(\App\Services\Settings\SystemSettingsService::class)->set('sms_mode','log'); \Illuminate\Support\Facades\Cache::forget('system_settings');"
docker compose exec app php artisan config:clear
docker compose exec app php artisan cache:clear
```

سپس در سایت «دریافت کد» بزنید و با **`123456`** وارد شوید.

### بعد از deploy جدید (دستور درست)

```bash
docker compose exec app php artisan system:sms-enable --log
```

**توجه:** فلگ `--log` وجود دارد؛ `--live` برای SMS واقعی است.

### فعال‌سازی SMS واقعی

```bash
# در backend/.env باید باشد:
# IPPANEL_USERNAME=...
# IPPANEL_PASSWORD=...
# IPPANEL_OTP_PATTERN_CODE=qhhly1nai3njev0

docker compose exec app php artisan system:sms-enable --live --from-env
docker compose exec app php artisan system:sms-test 09170577873 --otp --debug
```

---

## مشکل ۲: VirtualTourSeeder does not exist

یعنی کد تور مجازی هنوز روی سرور deploy نشده. ابتدا آخرین نسخه را بکشید:

```bash
cd /var/www/posheh
git fetch origin
git checkout cursor/fix-otp-tour-e117
git pull origin cursor/fix-otp-tour-e117
./scripts/deploy.sh cursor/fix-otp-tour-e117
```

یا بعد از merge شدن PR به `main`:

```bash
git pull origin main
./scripts/deploy.sh main
```

سپس:

```bash
docker compose exec app php artisan migrate --force
docker compose exec app php artisan db:seed --class=VirtualTourSeeder --force
```

دموی تور: `https://posheapp.ir/tour/demo-apartment-pasdaran`
