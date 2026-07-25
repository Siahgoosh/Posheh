# SEO و Sitemap — پوشه

## آدرس Sitemap برای گوگل

**این لینک را در Google Search Console ثبت کنید:**

```
https://posheapp.ir/sitemap.xml
```

Sitemap به‌صورت **داینامیک** از سرور ساخته می‌شود و شامل این صفحات است:

- صفحه اصلی، وبلاگ، ثبت‌نام، دانلود، تماس، حریم خصوصی، قوانین
- همه دسته‌های وبلاگ (`/blog/category/...`)
- همه مقالات منتشرشده (`/blog/{slug}`)

`robots.txt` هم به همین آدرس اشاره می‌کند: `https://posheapp.ir/robots.txt`

## کلمات کلیدی اصلی

| کلمه | کاربرد |
|------|--------|
| فایلینگ املاک | لندینگ، وبلاگ دسته filing، مقالات ثبت ملک |
| حسابداری املاک | لندینگ، دسته accounting، مقالات مالی |
| CRM املاک | لندینگ، دسته crm، قیف فروش |
| فروش ملک | مقالات CRM، قرارداد، بازاریابی |
| اجاره ملک | مقالات قرارداد، مدیریت مستأجر |

## ثبت در Google Search Console

1. وارد [Google Search Console](https://search.google.com/search-console) شوید
2. Property مربوط به `posheapp.ir` را انتخاب کنید
3. منوی **Sitemaps** → آدرس `sitemap.xml` را وارد کنید → Submit
4. پس از هر deploy با مقاله جدید، گوگل در crawl بعدی آن‌ها را می‌بیند (نیازی به آپلود دستی نیست)

## پس از Deploy

```bash
./scripts/deploy.sh main
```

`BlogSeeder` در deploy اجرا می‌شود و مقالات SEO را به‌روز می‌کند.

برای بررسی sitemap روی سرور:

```bash
curl -s https://posheapp.ir/sitemap.xml | head -40
```

## صفحات noindex (عمداً از ایندکس خارج)

- `/login` — ورود کاربران
- `/payment/callback` — بازگشت از درگاه
- `/p/{token}` — لینک خصوصی QR ملک
- پنل ادمین و داشبورد (`/dashboard`, `/panel`, ...)

## نکته فنی

فرانت‌اند React است و متا تگ‌ها با `SeoHead` در مرورگر اعمال می‌شوند. گوگل معمولاً JavaScript را اجرا می‌کند؛ برای ایندکس سریع‌تر، `index.html` و sitemap داینامیک پوشش اولیه را می‌دهند.
