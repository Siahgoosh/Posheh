# Content Rebuild Queue — پوشه

منبع اولویت‌بندی: `SEO-BLOG-AUDIT.md`  
تاریخ: 2026-08-12  
وضعیت: Top 10 اولویت‌دار بازسازی شده به‌صورت Draft (Batch1 + Phase4 Batch1) — بقیه در صف

## فرمول Priority Score (۰–۱۰۰)

```
Priority =
  SEO Potential (0–25)
+ Business Value (0–25)
+ Existing Authority / URL equity (0–15)
+ Search Intent clarity (0–10)
+ Content Improvement Potential (0–15)
+ Freshness Need (0–10)
```

| باند | برچسب |
|------|--------|
| ۹۰–۱۰۰ | P0 |
| ۷۵–۸۹ | P1 |
| ۵۵–۷۴ | P2 |
| ۰–۵۴ | P3 |

## Batch 1 — انتخاب‌شده (۵ مقاله)

| # | نوع نمونه | Slug | Priority | Intent | Business | Action |
|---|-----------|------|----------|--------|----------|--------|
| 1 | مهم تجاری | `best-real-estate-crm-software-iran` | P0 (96) | commercial | consideration→conversion | REWRITE |
| 2 | مهم اطلاعاتی | `mubayaeh-contract-form-125-guide` | P0 (93) | informational | consideration | REWRITE |
| 3 | متوسط | `cloud-vs-excel-real-estate-management` | P1 (82) | commercial | consideration | REWRITE |
| 4 | قدیمی/نازک | `property-qr-code-marketing` | P1 (78) | informational | awareness | REWRITE |
| 5 | ضعیف doorway | `crm-crm-guide-1` | P2 (61)→reposition | informational | awareness | REWRITE + differentiate |

جزئیات briefs: `content/blog-rebuild/batch-1/briefs/`  
محتوای نهایی در کد: `backend/app/Services/Blog/Rebuild/Batch1RebuiltArticles.php`

## Phase 4 Batch 1 — پنج مقالهٔ بعدی (Draft)

| Slug | Priority | Action | Code |
|------|----------|--------|------|
| `property-filing-tips-for-agents` | P0 | REWRITE | `Phase4Batch1RebuiltArticles` |
| `digital-transformation-real-estate-agency` | P0 | REWRITE | همان |
| `real-estate-accounting-commission-guide` | P0 | REWRITE | همان |
| `property-customer-matching-system` | P1 | REWRITE | همان |
| `real-estate-website-subdomain-guide` | P1 | REWRITE | همان |

Import: `php artisan blog:rebuild-batch 2 --force`

## صف بعدی (خلاصه خوشه‌ها — هنوز بازنویسی نشود)

### P0
- پیلار آموزشی تور مجازی ۳۶۰
- ایمپورت/ادغام کیفیت از `SEO-POSHE/articles/*.md` با map اسلاگ

### P1
- `telegram-whatsapp-bot-real-estate`
- `solo-agent-software-iran`
- `real-estate-kpi-reports-dashboard`
- اجاره‌نامه/رهن (جدید با disclaimer)
- پیش‌فروش ریسک‌ها (جدید با disclaimer)

### P2
- مقالات دسته‌ای با keyword P1 واقعی و intent مشخص (انتخاب دستی از CSV، نه هر ۲۰ قالب)

### P3 / بازبینی برای NOINDEX یا MERGE
- اکثر `*-{guide,tips,mistakes,...}-{n}` ژنراتور با شباهت بالا به پیلار
- تصمیم MERGE/REDIRECT فقط بعد از overlap analysis + داده Search Console

## Cannibalization notes (Batch 1)

| Keyword risk | URLs | تصمیم Batch 1 |
|--------------|------|----------------|
| CRM املاک | pillar vs `crm-crm-guide-1` | guide-1 به «فرآیند فروش آپارتمان» reposition شد |
| مبایعه نامه | فقط پیلار ۱۲۵ | Keep |
| سامانه ابری / اکسل | فقط cloud-vs-excel | Keep |

## قانون صف

- هم‌زمان بیش از یک Batch فعال برای rewrite کامل نشود.
- بعد از تأیید کیفیت Batch 1، Batch 2 حداکثر ۱۰ مقاله.
- `rebuild_locked=true` مانع overwrite توسط `blog:seed` است.
