# CONTENT-REBUILD-BATCH-1

**تاریخ:** 2026-08-12  
**شاخه:** `cursor/seo-content-rebuild-a876`  
**وضعیت انتشار:** همهٔ ۵ مقاله **Draft** (`is_published=false`, `review_status=content_review`, `robots=noindex,nofollow`, `rebuild_locked=true`)  
**قانون:** تا تأیید انسانی Publish نشود.

---

## Articles Processed

| # | Slug | نوع نمونه | Priority | Action |
|---|------|-----------|----------|--------|
| 1 | `best-real-estate-crm-software-iran` | مهم تجاری | P0 | REWRITE |
| 2 | `mubayaeh-contract-form-125-guide` | مهم اطلاعاتی | P0 | REWRITE |
| 3 | `cloud-vs-excel-real-estate-management` | متوسط | P1 | REWRITE |
| 4 | `property-qr-code-marketing` | قدیمی/نازک | P1 | REWRITE |
| 5 | `crm-crm-guide-1` | ضعیف doorway | P2→reposition | REWRITE + Differentiate |

- Articles Updated (content ready as draft import): **5**
- Articles Merged: **0**
- Articles Redirected: **0**
- Articles Rejected: **0**
- Auto-published: **0**

---

## Keyword Map (Batch 1)

| URL | Primary | Intent | Business | Notes |
|-----|---------|--------|----------|-------|
| `/blog/best-real-estate-crm-software-iran` | CRM املاک | commercial | consideration | پیلار |
| `/blog/mubayaeh-contract-form-125-guide` | مبایعه نامه | informational | consideration | disclaimer حقوقی |
| `/blog/cloud-vs-excel-real-estate-management` | سامانه ابری املاک | commercial | consideration | |
| `/blog/property-qr-code-marketing` | QR کد ملک | informational | awareness | |
| `/blog/crm-crm-guide-1` | فروش آپارتمان | informational | awareness | از رقابت با CRM خارج شد |

---

## Before / After Scores

امتیازها با `BlogContentQualityScorer` (ساختار، خوانایی، لینک، تصویر، اعتماد، تبدیل).  
**نکته:** قالب ژنراتور ممکن است امتیاز ساختاری بالا بگیرد ولی از نظر Originality در Audit برابر Grade D است.

| Slug | Before Overall | After Overall | Content | SEO | Readability | Internal Linking | Image |
|------|----------------|---------------|---------|-----|-------------|------------------|-------|
| best-real-estate-crm-software-iran | 60 | **92** | 39→83 | 70→100 | 88→100 | 8→98 | 70→70 |
| mubayaeh-contract-form-125-guide | 47 | **86** | 26→79 | 54→84 | 66→100 | 0→86 | 70→70 |
| cloud-vs-excel-real-estate-management | 52 | **84** | 34→83 | 54→76 | 76→100 | 8→74 | 70→70 |
| property-qr-code-marketing | 41 | **88** | 21→79 | 54→86 | 66→100 | 8→98 | 0→70 |
| crm-crm-guide-1 | 87* | **90** | 81→79 | 84→94 | 100→100 | 94→100 | 70→70 |

\*Before برای `crm-crm-guide-1` روی قالب ژنراتور (~۶۲۰ کلمه تکراری) است — امتیاز ساختاری گمراه‌کننده؛ ارزش تحریری قبل از بازسازی: **D / doorway**. بعد از reposition موضوع به «فرآیند فروش آپارتمان» تغییر کرد.

**میانگین Overall:** قبل ≈ **57.4** → بعد ≈ **88.0** (Δ +30.6)

---

## Quality Gate

برای هر ۵ مقاله (حالت Draft / `forPublish=false`):

- [x] Title / Slug / Content length
- [x] بدون H1 تکراری در بدنه
- [x] Meta title & description
- [x] Category / Focus keyword / Intent
- [x] ≥۲ internal link در متن
- [x] FAQ مرتبط (نه کپی ژنراتور)
- [x] Cover SVG محلی
- [x] CTA متناسب با intent (نرم، غیرکلیک‌بیت)
- [x] بدون آمار قیمت/مالیات ساختگی
- [ ] Publish blocked تا Approve انسانی

---

## Images Added

| File | ALT مفهومی | Prompt ذخیره در `image_prompt` |
|------|------------|----------------------------------|
| `/images/blog/crm-software-iran-cover.svg` | کاور مفهومی CRM املاک | دارد |
| `/images/blog/mubayaeh-form-125-cover.svg` | کاور مبایعه‌نامه | دارد |
| `/images/blog/cloud-vs-excel-cover.svg` | اکسل در برابر ابر | دارد |
| `/images/blog/property-qr-marketing-cover.svg` | QR و بازاریابی ملک | دارد |
| `/images/blog/apartment-sales-process-cover.svg` | فرآیند فروش آپارتمان | دارد |

تصاویر فعلی SVG برندینگ سبک هستند (جلوگیری از Unsplash تکراری). در فاز بعد می‌توان با WebP واقعی جایگزین کرد.

---

## Internal Links Added

هر مقاله ۳–۵ لینک contextual به پیلار/خوشه + `related_slugs` ۳–۵تایی.  
Anchorها توصیفی‌اند (نه «اینجا کلیک کنید»).

---

## SEO / Technical Improvements in this batch infra

1. فیلدهای rebuild: `focus_keyword`, `search_intent`, `business_intent`, `review_status`, `rebuild_locked`, `quality_scores`, `content_brief`, `canonical_url`, `robots_directive`, `scheduled_at`, `image_prompt`
2. جداول `blog_post_versions` + `blog_redirects`
3. `BlogContentQualityScorer` + `BlogQualityGate` (بلاک Publish در Admin API)
4. `php artisan blog:rebuild-batch 1` — فقط Draft
5. `blog:seed` و `BlogSeeder` دیگر پست‌های `rebuild_locked` را overwrite نمی‌کنند
6. `deploy.sh`: seed انبوه پیش‌فرض خاموش (`BLOG_SEED_ON_DEPLOY=1` برای محیط خالی)
7. حذف `public/robots.txt` استاتیک تا robots داینامیک Laravel برسد
8. رندر `meta robots` در Blade layout
9. Sitemap posts با `noindex` را حذف می‌کند
10. Admin `GET /api/admin/blog/health` برای داشبورد اولیه
11. جلوگیری از دوبل برند در title SSR وقتی meta قبلاً «پوشه» دارد

---

## Schema

JSON-LD موجود (Article + FAQPage + Breadcrumb) با محتوای جدید سازگار است.  
FAQهای جدید با متن صفحه هم‌خوان‌اند.  
تا قبل از Publish عمومی، مقالات draft در SSR عمومی نیستند.

---

## Remaining Problems

- UI کامل Content/SEO Health Dashboard در فرانت هنوز مینیمال است (API health اضافه شد)
- Auto-scheduler انتشار هنوز پیاده نشده
- Redirect manager UI ندارد (جدول آماده است)
- ~۲۹۵ مقاله ژنراتور هنوز در صف P2/P3
- تصاویر SVG موقت‌اند؛ WebP واقعی لازم است
- Preview اختصاصی ادمین هنوز همان editor است
- Search Console data برای اولویت دقیق‌تر در دسترس این محیط نبود

---

## How to import drafts on server

```bash
docker compose exec app php artisan migrate --force
docker compose exec app php artisan blog:rebuild-batch 1 --force
```

سپس در Admin: مشاهده → ویرایش → Approve → فقط بعد از تأیید `is_published=true`.

---

## STOP

Batch 1 تمام شد. **Batch 2 شروع نشود** تا کیفیت این ۵ مقاله تأیید شود.
