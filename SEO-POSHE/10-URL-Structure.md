# ساختار URL پوشه

> دامنه: `https://posheapp.ir` · زبان: `fa-IR` · RTL

## اصول کلی

| قانون | مقدار |
|-------|--------|
| پروتکل | HTTPS اجباری |
| www | بدون www (canonical) |
| trailing slash | یکسان (ترجیح: بدون `/` انتهایی برای مقالات) |
| حروف | lowercase برای اسلاگ انگلیسی |
| جداکننده | خط تیره `-` |
| فارسی در URL | فقط پیلارها و دسته‌ها (encode UTF-8) |
| مقالات blog | **اسلاگ انگلیسی** ([20-Slugs.md](20-Slugs.md)) |

---

## درخت URL

```
posheapp.ir/
├── /                          # صفحه اصلی
├── /features/
│   ├── /features/crm
│   ├── /features/filing
│   ├── /features/accounting
│   ├── /features/contracts
│   ├── /features/team
│   ├── /features/matching
│   ├── /features/website
│   ├── /features/mobile
│   ├── /features/reports
│   └── /features/integrations
├── /pricing
├── /register                  # ثبت‌نام · ۴۸h trial
├── /login
├── /about
├── /contact
├── /blog/
│   ├── /blog                          # آرشیو وبلاگ
│   ├── /blog/category/{slug}          # دسته — انگلیسی
│   ├── /blog/pillar/{slug-fa}         # پیلار — فارسی encode
│   └── /blog/{english-slug}           # مقاله
├── /sitemap.xml
└── /robots.txt
```

---

## الگوهای URL

### مقالات وبلاگ

```
https://posheapp.ir/blog/{english-slug}
```

**مثال‌ها:**

| عنوان فارسی | Slug | URL کامل |
|-------------|------|----------|
| راهنمای CRM املاک | `real-estate-crm-guide` | `/blog/real-estate-crm-guide` |
| چک‌لیست ثبت ملک | `property-filing-checklist` | `/blog/property-filing-checklist` |
| تحول دیجیتال آژانس | `digital-transformation-agency` | `/blog/digital-transformation-agency` |

۵۰ نمونه: [20-Slugs.md](20-Slugs.md)

### صفحات پیلار

```
https://posheapp.ir/blog/pillar/{slug}
```

| پیلار | Slug |
|-------|------|
| نرم افزار املاک | `نرم-افزار-املاک` |
| CRM املاک | `crm-املاک` |
| ثبت ملک | `ثبت-ملک-فایلینگ` |

لیست ۳۰ پیلار: [06-Pillar-Pages.md](06-Pillar-Pages.md)

### دسته‌بندی

```
https://posheapp.ir/blog/category/{english-slug}
```

| دسته | Slug |
|------|------|
| CRM | `crm` |
| نرم‌افزار | `software` |
| فایلینگ | `filing` |

۱۵ دسته: [05-Blog-Categories.md](05-Blog-Categories.md)

---

## پارامترهای URL

| وضعیت | سیاست |
|-------|--------|
| UTM (`?utm_*`) | مجاز · canonical بدون پارامتر |
| صفحه‌بندی (`?page=2`) | `rel=next/prev` یا view-all canonical |
| فیلتر وبلاگ | ترجیح: URL تمیز `/blog/category/crm` |
| session id | ممنوع در URL ایندکس‌شونده |

---

## Canonical

```html
<link rel="canonical" href="https://posheapp.ir/blog/real-estate-crm-guide" />
```

- هر صفحه **یک** canonical
- نسخه چاپ / AMP → canonical به نسخه اصلی
- HTTP → HTTPS

---

## hreflang

```html
<link rel="alternate" hreflang="fa-IR" href="https://posheapp.ir/blog/real-estate-crm-guide" />
<link rel="alternate" hreflang="x-default" href="https://posheapp.ir/blog/real-estate-crm-guide" />
```

فعلاً تک‌زبانه فارسی؛ در صورت نسخه انگلیسی بعداً اضافه شود.

---

## breadcrumbs (Schema + UI)

```
خانه > وبلاگ > CRM > راهنمای CRM املاک
```

```json
"@type": "BreadcrumbList"
```

جزئیات: [16-Schema.md](16-Schema.md)

---

## ریدایرکت

| از | به | کد |
|----|-----|-----|
| www.posheapp.ir | posheapp.ir | 301 |
| HTTP | HTTPS | 301 |
| /blog/post?id=123 | /blog/slug | 301 |
| slug قدیمی | slug جدید | 301 |

**ممنوع:** زنجیره بیش از ۱ hop · 302 برای محتوای دائمی

---

## robots.txt

```
User-agent: *
Allow: /
Disallow: /admin/
Disallow: /api/
Disallow: /login
Sitemap: https://posheapp.ir/sitemap.xml
```

---

## sitemap

| sitemap | محتوا | اولویت |
|---------|--------|--------|
| `sitemap-index.xml` | ارجاع به زیرنقشه‌ها | — |
| `sitemap-pages.xml` | خانه، features، pricing | 1.0 |
| `sitemap-pillars.xml` | ۳۰ پیلار | 0.9 |
| `sitemap-blog.xml` | مقالات | 0.7 |
| `lastmod` | تاریخ واقعی ویرایش | — |

---

## خطاهای رایج — اجتناب

1. ❌ `blog/crm-املاک` و `blog/crm-real-estate` برای یک مقاله
2. ❌ underscore: `real_estate_crm`
3. ❌ تاریخ در slug: `crm-guide-2026`
4. ❌ slug بلندتر از ۶۰ کاراکتر
5. ❌ تغییر slug بدون ۳۰۱

---

## لینک‌های مرتبط

- [09-Internal-Linking.md](09-Internal-Linking.md)
- [12-Technical-SEO.md](12-Technical-SEO.md)
- [20-Slugs.md](20-Slugs.md)
- [16-Schema.md](16-Schema.md)
