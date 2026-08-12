# PHASE-4-BATCH-1-REPORT

**تاریخ:** 2026-08-12  
**شاخه:** `cursor/production-release-a876`  
**وضعیت انتشار:** هر ۱۰ مقاله **Draft** (`is_published=false`, `rebuild_locked=true`, `robots=noindex,nofollow`, `review_status=content_review`)  
**قانون:** بدون Approve انسانی Publish نشود.

---

## Scope

Top 10 priority URLs (Traffic preservation: **هیچ slug عوض نشد**).

| # | Slug | Source batch | Action | Intent |
|---|------|--------------|--------|--------|
| 1 | `best-real-estate-crm-software-iran` | rebuild-batch 1 | REWRITE | commercial |
| 2 | `mubayaeh-contract-form-125-guide` | rebuild-batch 1 | REWRITE | informational (+legal warning) |
| 3 | `cloud-vs-excel-real-estate-management` | rebuild-batch 1 | REWRITE | commercial |
| 4 | `property-qr-code-marketing` | rebuild-batch 1 | REWRITE | informational |
| 5 | `crm-crm-guide-1` | rebuild-batch 1 | REWRITE + reposition | informational |
| 6 | `property-filing-tips-for-agents` | rebuild-batch 2 | REWRITE | informational |
| 7 | `digital-transformation-real-estate-agency` | rebuild-batch 2 | REWRITE | informational |
| 8 | `real-estate-accounting-commission-guide` | rebuild-batch 2 | REWRITE | commercial |
| 9 | `property-customer-matching-system` | rebuild-batch 2 | REWRITE | informational |
| 10 | `real-estate-website-subdomain-guide` | rebuild-batch 2 | REWRITE | commercial |

کد:

- `Batch1RebuiltArticles.php`
- `Phase4Batch1RebuiltArticles.php`
- Import: `php artisan blog:rebuild-batch 1 --force` و `blog:rebuild-batch 2 --force`

---

## URLs Changed / Redirects

- URLs Changed: **0**
- Redirects added: **0**
- Merged: **0**
- Deleted: **0**
- Auto-published: **0**

---

## Images Added (Batch 2 / Phase4)

| File | Purpose |
|------|---------|
| `/images/blog/property-filing-tips-cover.svg` | Featured |
| `/images/blog/digital-transformation-cover.svg` | Featured |
| `/images/blog/accounting-commission-cover.svg` | Featured |
| `/images/blog/customer-matching-cover.svg` | Featured |
| `/images/blog/website-subdomain-cover.svg` | Featured |

(پوشش Batch1 از قبل موجود است.)

---

## Internal links / Schema / SEO

- Internal links: هر مقاله ≥۲ لینک به `/blog/...` یا CTA منطقی؛ related_slugs تکمیل شد
- Schema: از مسیر Blog CMS موجود (BlogPosting/FAQ/Breadcrumb) — بدون fake review
- Meta title/description/focus keyword/intent برای هر ۱۰ تنظیم شد
- Legal warning در مبایعه‌نامه و حسابداری/کمیسیون

---

## Quality scores

امتیاز داخلی `BlogContentQualityScorer` — **نه** «Google SEO Score».

| Slug | After overall (approx band) | Gate draft |
|------|-----------------------------|------------|
| Batch1 ×۵ | ~84–92 (گزارش CONTENT-REBUILD-BATCH-1) | pass |
| property-filing-tips-for-agents | target ≥60 | pass expected |
| digital-transformation-real-estate-agency | target ≥60 | pass expected |
| real-estate-accounting-commission-guide | target ≥60 | pass expected |
| property-customer-matching-system | target ≥60 | pass expected |
| real-estate-website-subdomain-guide | target ≥60 | pass expected |

تست واحد: `BlogContentRebuildBatch1Test`, `BlogContentRebuildPhase4Batch1Test`.

---

## Errors / Warnings

| Item | Severity | Note |
|------|----------|------|
| GSC metrics empty | Warning | بدون credentials |
| Generator 300 still in DB if previously seeded | Warning | seed دیگر روی deploy نیست؛ curate تدریجی |
| Publish blocked | Info | by design |

---

## Search Console Data

در دسترس نیست در این اجرا.

---

## Deploy note (مهم)

فقط از شاخهٔ یکپارچه دیپلوی کنید:

```bash
./scripts/deploy.sh cursor/production-release-a876
docker compose exec -T app php artisan migrate --force
docker compose exec -T app php artisan blog:cms-bootstrap
docker compose exec -T app php artisan blog:rebuild-batch 1 --force
docker compose exec -T app php artisan blog:rebuild-batch 2 --force
```

`BLOG_SEED_ON_DEPLOY` و `BLOG_REBUILD_ON_DEPLOY` پیش‌فرض خاموش‌اند.

---

## Next batch gate

پس از QA انسانی روی این ۱۰ draft:

1. Publish انتخابی (نه همه با هم اگر CTR risk)
2. Batch بعدی حداکثر ۱۰–۲۵ از P1 صف
3. قبل از MERGE/NOINDEX توده‌های ژنراتور: داده GSC
