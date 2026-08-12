# PHASE-4 — Full Website SEO Audit (Code + Architecture Crawl)

**تاریخ:** 2026-08-12  
**شاخه:** `cursor/production-release-a876`  
**دامنهٔ تحلیل:** کدبیس + ساختار route/sitemap/blog generator (بدون دسترسی مستقیم GSC در این محیط)  
**وضعیت GSC API:** غیرفعال مگر `BLOG_GSC_ENABLED` + credentials تنظیم شود

---

## 4.1 Technical crawl summary

| حوزه | وضعیت | یادداشت |
|------|--------|---------|
| HTTPS | وابسته به سرور/nginx | در پروداکشن روی posheapp.ir فرض TLS |
| robots.txt | Dynamic Laravel `/robots.txt` | فایل استاتیک `public/robots.txt` حذف شد تا سایه نیندازد |
| Sitemap index | `/sitemap.xml` | children: pages, posts, categories, tours (+ alias blog) |
| Canonical | در Blade/SEO helpers | per-post `canonical_url` پشتیبانی می‌شود |
| H1 | قالب وبلاگ | بدنه نباید H1 تکراری داشته باشد (Quality Gate) |
| Schema | BlogPosting + FAQ + Breadcrumb | Fake review/aggregate rating ممنوع |
| Pagination | لیست وبلاگ | از سقف ۵۰ مقاله در فازهای قبل ارتقا یافته؛ ادامه پایش لازم |
| Soft-404 | Blog not-found | پاسخ ۴۰۴ واقعی برای اسلاگ ناموجود |
| Indexability drafts | `noindex,nofollow` | rebuild drafts تا Approve انسانی |
| Performance | خارج از اندازه دقیق این محیط | پیشنهاد: Lighthouse بعد از دیپلوی release |

---

## 4.2 URL inventory (قابل دسترسی از routeها)

### Public / Marketing

| URL pattern | Type | Indexability |
|-------------|------|--------------|
| `/` | UTILITY/landing | index |
| `/blog` | PILLAR hub | index |
| `/blog/{slug}` | Article | per robots |
| `/blog/category/{slug}` | SUPPORTING | index |
| `/blog/tag/{slug}` | SUPPORTING | index |
| `/blog/author/{slug}` | UTILITY | index |
| `/blog/search` | UTILITY | **noindex** (robots) |
| `/blog/preview/{token}` | UTILITY | **noindex** |
| `/feed` | UTILITY | allow |
| `/tour/{slug}` | COMMERCIAL/product | index when published |
| `/p/{…}` `/o/{…}` | LOCAL/public property/office | index per rules |
| `/register` `/download` `/contact` | TRANSACTIONAL/UTILITY | index |

### App (disallow in robots)

`/dashboard`, `/properties`, `/crm`, `/accounting`, `/admin`, `/api/`, `/team-chat`, `/tickets`, `/embed/`

### Blog corpus size (generator)

- Pillars: **۱۲** اسلاگ استراتژیک
- Template variants: تا **~۳۰۰** مقاله ژنتیک (ریسک DUPLICATE/THIN)
- Rebuilt drafts (تا این فاز): **۱۰** (۵+۵)

CSV کامل URL×metrics وقتی DB پروداکشن + GSC وصل شود در Admin export / `blog_gsc_metrics` تکمیل می‌شود.

---

## 4.3 Content classification (استراتژیک)

| Class | مثال‌ها |
|-------|---------|
| PILLAR | CRM ایران، فایلینگ، تحول دیجیتال، حسابداری کمیسیون، مبایعه‌نامه ۱۲۵ |
| SUPPORTING | تطبیق ملک/مشتری، ساب‌دامین، QR، اکسل vs ابری |
| LONG_TAIL | guide/tips/… شهر×نوع (ژنراتور) |
| COMMERCIAL | CRM، وبسایت دفتر، نرم‌افزار مشاور مستقل |
| INFORMATIONAL | فرآیند فروش آپارتمان، نکات حقوقی (با disclaimer) |
| THIN / DUPLICATE | اکثر `-{variant}-{n}` نزدیک به پیلار |
| UTILITY | search, preview, feed |

---

## 4.4–4.5 Decision engine + traffic preservation

برای ۱۰ اولویت بالا: **REWRITE روی همان URL** (بدون تغییر اسلاگ).  
برای توده‌های ژنراتور: فعلاً **KEEP + no mass delete** تا داده GSC/بک‌لینک بیاید؛ سپس MERGE/NOINDEX انتخابی.

---

## 4.6–4.9 Search Console / Quick wins / Decay

**داده GSC در این محیط موجود نیست.**  
پس از اتصال:

1. Quick wins: position 4–10 + CTR پایین → title/meta/FAQ
2. Striking distance: 11–20 → expand + internal links
3. Decay: مقایسه ۲۸ روز قبل/بعد از publish batch

Placeholder جدول در Admin: `blog_gsc_metrics`.

---

## 4.10 Cannibalization (اولیه)

| Intent | URLs | Primary | Action |
|--------|------|---------|--------|
| CRM املاک | pillar + crm-*-guide | `best-real-estate-crm-software-iran` | guide-1 reposition شد |
| فایلینگ | pillar + filing-* templates | `property-filing-tips-for-agents` | templates: support یا noindex بعدی |
| حسابداری/کمیسیون | pillar + commission-* | `real-estate-accounting-commission-guide` | KEEP pillar |
| مبایعه‌نامه | form-125 | همان | KEEP |

---

## 4.11–4.13 Topic clusters & gaps

### Clusters

1. **CRM & Sales** — pillar CRM → matching, sales process, KPI  
2. **Filing & Ops** — filing → QR, cloud vs excel, digital 90d  
3. **Contracts & Trust** — mubayaeh 125 (+ legal disclaimer)  
4. **Money** — accounting/commission  
5. **Presence** — website subdomain, bots, tour (product)

### Content gaps (اولویت)

| TOPIC | COVERAGE | MISSING | INTENT | BIZ | PRIORITY |
|-------|----------|---------|--------|-----|----------|
| اجاره‌نامه / رهن | ضعیف | راهنمای عملی اجاره | info | mid | P1 |
| پیش‌فروش | ضعیف | ریسک‌ها + چک‌لیست | info/legal | mid | P1 |
| تور ۳۶۰ برای مشاور | محصول هست | مقاله پیلار آموزشی | commercial | high | P0 |
| سرقفلی | تقریباً نیست | فقط با منبع | info | low | P2 |
| محله‌ای واقعی تهران | doorway زیاد/کیفیت کم | فقط با داده واقعی | local | high | P1 (نه doorway) |

---

## Stop conditions چک‌شده

- URL migration بدون redirect: **انجام نشد**
- Mass publish: **انجام نشد**
- Fake stats/schema: **اجتناب شد**
- Deploy feature wipe: **با release branch رفع شد**
