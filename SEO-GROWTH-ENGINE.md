# SEO Growth Engine — پوشه

**تاریخ:** 2026-08-12  
**شاخه:** `cursor/production-release-a876`  
**وضعیت:** Phase 5 پیاده‌سازی شده — بدون auto-rewrite / auto-merge / fake metrics

---

## هدف

تبدیل وبلاگ به چرخه:

```
DATA → ANALYZE → DISCOVER → PRIORITIZE → OPTIMIZE → PUBLISH → MEASURE → LEARN
```

با اولویت دادهٔ واقعی (GSC/first-party) و تأیید انسانی برای اقدامات خطرناک.

---

## اجزای اصلی

| لایه | مسیر |
|------|------|
| Config | `backend/config/seo.php` + `blog.gsc` |
| Warehouse | migration `2026_08_12_120000_create_seo_growth_engine_tables.php` |
| Collector | `SeoGscCollector` (`seo:collect-gsc`) |
| Intelligence | `SeoQueryIntelligence`, `SeoOpportunityEngine`, `SeoPriorityScorer` |
| Recommendations | `SeoRecommendationService` (Approve / Execute / Rollback) |
| Dashboard API | `SeoGrowthAdminController` → `/api/v1/admin/seo/*` |
| UI | `/admin/seo-growth` → `AdminSeoGrowthPage` |
| Internal search | `SeoInternalSearchLogger` روی `/blog/search` |

---

## سطوح Automation

| سطح | مثال |
|-----|------|
| AUTOMATIC | جمع‌آوری GSC، Analyze زمان‌بندی‌شده، مانیتورینگ، sitemap/cache |
| ASSISTED | Quick Win، لینک داخلی related_slugs، پیشنهاد Title |
| MANUAL | Merge، Redirect، Major Rewrite، Noindex |

STAR portfolio → Major Rewrite خودکار **ممنوع** (Refresh Protection).

---

## Cron

```
seo:collect-gsc          daily 03:15
seo:analyze              weekly Mon 04:00
seo:analyze --weekly-report  weekly Mon 04:30
```

اگر GSC قطع باشد: وضعیت `DATA_UNAVAILABLE` — Blog خراب نمی‌شود.

---

## اتصال GSC

```env
BLOG_GSC_ENABLED=1
BLOG_GSC_PROPERTY=https://posheapp.ir/
BLOG_GSC_CREDENTIALS_JSON=/absolute/path/service-account.json
```

بدون credentials هیچ عدد ترافیکی جعل نمی‌شود.

---

## جریان Approval (Acceptance)

1. `seo:collect-gsc` یا دکمه جمع‌آوری
2. `seo:analyze` → Opportunity + Recommendation
3. مدیر در Dashboard تا ۱۰ اولویت را می‌بیند
4. Approve
5. Execute (safe) — snapshot نسخه
6. Rollback در صورت نیاز
7. تغییرات بزرگ محتوا فقط در CMS ادیتور

---

## اسناد وابسته

- `SEO-DATA-DICTIONARY.md`
- `SEO-RECOMMENDATION-LOG.md`
- `SEO-MONITORING-PLAN.md`
- `FINAL-SEO-REPORT.md` (فاز ۴)
- `docs/DEPLOY-PRODUCTION-RELEASE.md`

---

## STOP / Safety

- بدون Ranking guarantee
- بدون Fake clicks/impressions
- بدون URL change بدون Redirect Plan
- بدون Publish/Rewrite انبوه خودکار
