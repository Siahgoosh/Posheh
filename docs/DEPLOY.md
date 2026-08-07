# راهنمای Deploy یکجا — پوشه

آخرین به‌روزرسانی: ۱۴۰۴/۰۵/۱۳

## مشکل شما (نام شاخه اشتباه)

اگر این خطا را دیدید:

```text
fatal: couldn't find remote ref cursor/visit/notifications-e117
```

**علت:** نام شاخه با `/` اشتباه است. نام درست با **خط تیره** است:

| ❌ اشتباه | ✅ درست |
|-----------|---------|
| `cursor/visit/notifications-e117` | `cursor/visit-notifications-e117` |
| `cursor/virtual/tour/enterprise-e117` | `cursor/virtual-tour-enterprise-e117` |

## دستور Deploy (همه فیچرها یکجا)

شاخه **`cursor/release-deploy-e117`** (یا **`cursor/mobile-pwa-releases-e117`** — آخرین PWA + بیلد) شامل:

- تور مجازی ۳۶۰ (Enterprise + رفع loading ۰٪)
- نوتیفیکیشن درخواست بازدید (پنل + تلگرام)
- زنگوله اعلان در پنل
- درخواست‌های وبسایت در صفحه بازدیدها
- بهبود سایز PWA/موبایل وب (safe-area، هدر موبایل)
- نسخه اپ 1.0.3+8 (اندروید/ویندوز — CI بیلد پس از push)

```bash
cd /var/www/posheh
./scripts/deploy.sh cursor/release-deploy-e117
```

اسکریپت deploy بعضی نام‌های اشتباه را خودکار اصلاح می‌کند (مثلاً `cursor/visit/notifications-e117`).

## پیش‌نیاز روی سرور

- Docker و Docker Compose v2
- `git` و دسترسی به `origin`
- **PHP روی host لازم نیست** — همه دستورات `php artisan` داخل کانتینر `app` اجرا می‌شوند

## بعد از Deploy — چک سریع

| آیتم | آدرس / دستور |
|------|----------------|
| API | `curl -s -o /dev/null -w "%{http_code}" http://localhost:8000/api/v1/plans` → `200` |
| پانوراما دمو | `http://YOUR_DOMAIN/demo/sphere.jpg` → `200` |
| تور دمو | `/tour/demo-apartment-pasdaran` |
| پنل ادمین | `https://panel.posheapp.ir` |
| مایگریشن دستی | `./scripts/migrate.sh` |

## تست نوتیفیکیشن بازدید

1. از وبسایت دفتر (`name.posheapp.ir`) درخواست بازدید ثبت کنید
2. در پنل مدیر: زنگوله بالا چپ → اعلان جدید
3. صفحه `/visits` → بخش «درخواست‌های بازدید از وبسایت»
4. اگر تلگرام تنظیم است: پیام در چت مدیر (توکن + User ID در تنظیمات)

## تست تور مجازی

1. `/tour/demo-apartment-pasdaran` — پانوراما باید لود شود (نه ۰٪ بی‌پایان)
2. آپلود پانوراما در ویرایشگر
3. انتشار تور → لینک عمومی

## اگر fetch شاخه fail شد

```bash
cd /var/www/posheh
git fetch origin
git branch -r | grep release-deploy
./scripts/deploy.sh cursor/release-deploy-e117
```

## شاخه‌های دیگر (فقط در صورت نیاز)

| شاخه | محتوا |
|------|--------|
| `cursor/release-deploy-e117` | **همه فیچرهای فعلی — پیشنهاد اصلی** |
| `cursor/visit-notifications-e117` | همان محتوا (بدون بهبود deploy script) |
| `cursor/virtual-tour-enterprise-e117` | فقط تور مجازی (بدون نوتیف بازدید) |
| `main` | پایه production (ممکن است فیچرهای جدید نباشد) |

## مستندات مرتبط

- [VISIT-NOTIFICATIONS.md](./VISIT-NOTIFICATIONS.md) — نوتیف بازدید وبسایت
- [VIRTUAL-TOUR.md](./VIRTUAL-TOUR.md) — تور مجازی
- [INSTALLATION.md](./INSTALLATION.md) — نصب اولیه

## بیلد اندروید و ویندوز

پس از push، GitHub Actions workflow **Build Android & Windows** APK و ZIP را می‌سازد و در `frontend/public/downloads/` commit می‌کند.

```bash
# محلی
./scripts/build-releases.sh
```

| فایل | URL |
|------|-----|
| Android APK | https://posheapp.ir/downloads/posheh-android.apk |
| Windows ZIP | https://posheapp.ir/downloads/posheh-windows.zip |

نسخه: `mobile/pubspec.yaml` → فعلی **1.0.3+8**
