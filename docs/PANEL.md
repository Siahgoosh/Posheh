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
git pull
cd frontend && npm run build
docker compose restart nginx
```

تنظیم توکن کافه‌بازار روی سرور:

```bash
./scripts/set-cafe-bazaar-env.sh 'JWT_TOKEN'
```

## APK اندروید

https://posheapp.ir/downloads/posheh-android.apk — نسخه `1.0.2+7`
