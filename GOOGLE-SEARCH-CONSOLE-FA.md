# اتصال وبلاگ پوشه به Google Search Console

این راهنما برای اتصال دامنهٔ واقعی (مثلاً `https://posheapp.ir/`) به **کنسول جستجوی گوگل** و فعال‌سازی جمع‌آوری داده در پوشه است.

> ارسال نقشه سایت ≠ تضمین ایندکس. بدون داده واقعی، سیستم متریک جعلی نمی‌سازد و وضعیت `نامشخص / DATA_UNAVAILABLE` نشان می‌دهد.

---

## ۱) ثبت و تأیید مالکیت در کنسول جستجو

1. به [Google Search Console](https://search.google.com/search-console) بروید.
2. **افزودن property** را بزنید.
3. ترجیحاً نوع **Domain** (کل دامنه) یا **URL prefix** با `https://posheapp.ir/` را انتخاب کنید.
4. مالکیت را با یکی از روش‌ها تأیید کنید:
   - DNS TXT (پیشنهادی برای Domain)
   - HTML file / meta tag (برای URL prefix)
5. پس از تأیید، در بخش **Sitemaps** این آدرس‌ها را ثبت کنید:
   - `https://posheapp.ir/sitemap.xml` (ایندکس نقشه سایت)
   - در صورت نیاز: `sitemap-blog.xml` / `sitemap-locations.xml` (اگر جداگانه در robots یا ایندکس آمده‌اند)

---

## ۲) ساخت Service Account برای API

برای همگام‌سازی کلیک/نمایش/جایگاه داخل پنل پوشه:

1. در [Google Cloud Console](https://console.cloud.google.com/) یک پروژه بسازید یا انتخاب کنید.
2. API **Google Search Console API** را Enable کنید.
3. یک **Service Account** بسازید و کلید JSON بگیرید.
4. ایمیل Service Account را در Search Console → Settings → Users and permissions به عنوان کاربر (حداقل Full یا Restricted با دسترسی خواندن گزارش‌ها) اضافه کنید.

---

## ۳) متغیرهای محیطی پوشه

در `.env` سرور (Docker/production):

```env
BLOG_GSC_ENABLED=1
BLOG_GSC_PROPERTY=https://posheapp.ir/
BLOG_GSC_CREDENTIALS_JSON=/absolute/path/to/service-account.json
```

نکته‌ها:

- مسیر JSON باید داخل کانتینر/سرور قابل خواندن باشد.
- مقدار `BLOG_GSC_PROPERTY` باید دقیقاً با property تأییدشده در کنسول یکی باشد (معمولاً با `/` پایانی برای URL-prefix).
- تا وقتی credentials معتبر نباشد، جمع‌آوری خاموش/ناموفق می‌ماند و داده جعلی تولید نمی‌شود.

سپس:

```bash
php artisan config:clear
php artisan seo:collect-gsc
```

یا از پنل: **رشد سئو → جمع‌آوری کنسول جستجو**.

زمان‌بندی روزانه هم در کرون تعریف شده: `seo:collect-gsc` حدود ساعت ۰۳:۱۵.

---

## ۴) بررسی صحت اتصال

| نشانه | معنی |
|---|---|
| داشبورد وبلاگ / رشد سئو: وضعیت OK + ردیف پرس‌وجو | اتصال و sync موفق |
| `CONFIGURED_NO_ROWS` | credentials هست ولی هنوز ردیفی نیامده (صبر یا بازه داده) |
| `DATA_UNAVAILABLE` / نامشخص | فعال نیست یا credentials/API مشکل دارد |

از پنل ادمین:

- `/admin/seo-growth` → کیفیت داده و برترین پرس‌وجوها
- `/admin/blog` → کارت کنسول جستجو (اگر در داشبورد آمده)

---

## ۵) چک‌لیست سریع بعد از اتصال

- [ ] Property تأیید شده
- [ ] Sitemap ثبت شده
- [ ] Service Account به property اضافه شده
- [ ] `BLOG_GSC_*` روی سرور ست شده
- [ ] یک بار `seo:collect-gsc` اجرا شده
- [ ] در پنل، متریک‌ها دیگر «نامشخص» نیستند (پس از چند روز داده کنسول)

---

## ۶) محدودیت‌های مهم

- کنسول جستجو ترافیک را با تأخیر (معمولاً ۱–۳ روز) نشان می‌دهد.
- امتیاز سئوی داخل پنل پوشه **نمره گوگل نیست**.
- ارسال نقشه سایت ایندکس را تضمین نمی‌کند.
- بدون داده واقعی، فرصت‌های رشد/CRO فیلدهای کلیک و نمایش را جعل نمی‌کنند.
