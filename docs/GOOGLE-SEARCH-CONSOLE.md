# Google Search Console — راه‌اندازی سئو پوشه

## علت مشکل قبلی

سایت React (SPA) است. گوگل برای `/blog/...` فقط `index.html` خالی می‌دید و **محتوای مقاله را نمی‌خواند**.

از این نسخه:
- **Googlebot** صفحات `/` و `/blog/*` را به صورت **HTML کامل** از سرور می‌گیرد
- کاربران عادی همان SPA React را می‌بینند

---

## مرحله ۱ — Deploy

```bash
cd /var/www/posheh
git pull origin cursor/complete-platform-e117
./scripts/deploy.sh cursor/complete-platform-e117
```

در `backend/.env` حتماً باشد:
```env
FRONTEND_URL=https://posheapp.ir
APP_URL=https://posheapp.ir
```

```bash
docker compose restart nginx app
```

---

## مرحله ۲ — تست قبل از Search Console

```bash
# robots
curl -s https://posheapp.ir/robots.txt

# sitemap (باید XML با URLها برگردد)
curl -s https://posheapp.ir/sitemap.xml | head -30

# صفحه اصلی برای گوگل (باید h1 و متن ببینی، نه فقط div خالی)
curl -s -A "Googlebot" https://posheapp.ir/ | head -40

# یک مقاله بلاگ
curl -s -A "Googlebot" https://posheapp.ir/blog | head -40
```

---

## مرحله ۳ — Google Search Console

1. برو [search.google.com/search-console](https://search.google.com/search-console)
2. **Add property** → `https://posheapp.ir` (ترجیحاً URL-prefix با https)
3. تأیید مالکیت — یکی از این روش‌ها:
   - **DNS TXT** در Cloudflare (پایدارترین)
   - **HTML meta tag** — تگ را در `frontend/index.html` قبل از `</head>` بگذار:
     ```html
     <meta name="google-site-verification" content="کد-گوگل" />
     ```
     سپس `npm run build` و deploy
4. **Sitemaps** → اضافه کن: `https://posheapp.ir/sitemap.xml`
5. **URL Inspection** → `https://posheapp.ir/` → Request Indexing
6. همین کار را برای `https://posheapp.ir/blog` و ۲–۳ مقاله انجام بده

---

## مرحله ۴ — Cloudflare

| تنظیم | مقدار |
|-------|--------|
| SSL | Full (strict) |
| Always Use HTTPS | On |
| `www` | Redirect به `posheapp.ir` یا property جدا |

مطمئن شو **Bot Fight Mode** گوگل را بلاک نمی‌کند.

---

## زمان ایندکس

- sitemap ثبت‌شده: معمولاً ۲۴–۷۲ ساعت تا اولین صفحات
- مقالات جدید: بعد از publish، در Search Console «Request indexing» بزن
- برای رشد سریع‌تر: لینک از اینستاگرام/لینکدین به `/blog` و `/register`

---

## کافه‌بازار

لیستینگ جدا از سئو وب است. برای ASO در بازار:
- عنوان و توضیحات از `docs/store-assets/CAFE-BAZAAR-LISTING-PACK.md`
- اسکرین‌شات‌های به‌روز
- لینک سایت: `https://posheapp.ir`

---

*بعد از deploy اگر sitemap خطا داد، خروجی `curl https://posheapp.ir/sitemap.xml` را بفرست.*
