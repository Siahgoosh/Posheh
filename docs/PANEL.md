# پنل مدیریت پلتفرم — panel.posheapp.ir

## دسترسی

- **URL:** https://panel.posheapp.ir
- **ورود:** OTP با شماره موبایل مدیر سیستم یا مدیران پلتفرم
- **جداسازی:** پنل کاملاً جدا از پنل دفاتر (Tenant) است

## نقش‌های پلتفرم

| نقش | دسترسی |
|-----|--------|
| `super_admin` | کامل |
| `platform_admin` | کامل |
| `platform_support` | دفاتر، کاربران، تیکت |
| `platform_finance` | پرداخت، اشتراک، کیف پول |

افزودن مدیر از بخش **مدیران پلتفرم** در پنل.

## DNS

رکورد `A` یا `CNAME` برای `panel.posheapp.ir` به همان سرور `posheapp.ir`.

Nginx wildcard `*.posheapp.ir` از قبل پشتیبانی می‌کند.

## استقرار

```bash
./scripts/deploy.sh main
```

یا دستی:

```bash
git pull origin main
cd frontend && npm ci && npm run build
docker compose exec app php artisan migrate --force
docker compose restart nginx
```

بعد از deploy، `panel.posheapp.ir` باید صفحه **«پنل مدیریت پلتفرم»** را نشان دهد (نه لندینگ).

## ایمیل

راه‌اندازی و رفع مشکل: `docs/EMAIL-SETUP.md`

```bash
cp docker/mail/secrets.env.example docker/mail/secrets.env
nano docker/mail/secrets.env   # MAIL_INFO_PASSWORD=...
./scripts/setup-mail.sh
```

اگر ایمیل کار نمی‌کند: `./scripts/fix-mail-restart.sh`

## کافه‌بازار

```bash
./scripts/set-cafe-bazaar-env.sh 'JWT_TOKEN'
```

## APK اندروید

https://posheapp.ir/downloads/posheh-android.apk — نسخه `1.0.2+7`
