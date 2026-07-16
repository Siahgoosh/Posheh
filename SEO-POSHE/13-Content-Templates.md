# قالب تولید محتوا (Content Templates)

> برای نویسندگان و AI · [11-SEO-Rules.md](11-SEO-Rules.md) · [21-Cursor-Rules.md](21-Cursor-Rules.md)

## قالب استاندارد مقاله وبلاگ

هر مقاله منتشرشده در `posheapp.ir/blog` از این ساختار پیروی می‌کند.

---

## بلوک متادیتا (YAML front matter)

```yaml
---
seo_title: "راهنمای کامل CRM املاک برای آژانس‌ها ۱۴۰۴"
meta_title: "CRM املاک | راهنمای آژانس ۱۴۰۴ — پوشه"
meta_description: "CRM املاک چیست و چگونه سرنخ و مشتری را مدیریت کنید؟ راهنمای عملی با پوشه — تطبیق ملک، KPI و ۴۸ ساعت رایگان."
slug: real-estate-crm-guide
primary_keyword: "CRM املاک"
secondary_keywords: ["مدیریت مشتری املاک", "سرنخ املاک", "نرم افزار CRM املاک"]
pillar: pillar-crm
category: crm
persona: manager
intent: informational
author: "تیم محتوای پوشه"
published: 2026-07-21
updated: 2026-07-21
reading_time: 12
---
```

---

## ساختار بدنه

```markdown
# {H1 — همان seo_title یا کوتاه‌تر}

> خلاصه ۲–۳ جمله‌ای با کلمه اصلی و وعده مطالعه

## فهرست مطالب
- [بخش ۱](#بخش-۱)
- [بخش ۲](#بخش-۲)
- ...
- [سوالات متداول](#سوالات-متداول)

![تصویر شاخص](path) <!-- ALT: ... -->

## مقدمه
{پاراگراف با کلمه کلیدی در ۱۰۰ کلمه اول}

## {H2 اول — تعریف / چرا مهم است}
### {H3 در صورت نیاز}

## {H2 دوم — روش / گام‌ها}
1. گام اول
2. گام دوم

| ستون ۱ | ستون ۲ |
|--------|--------|

## {H2 — نقش پوشه}
{معرفی طبیعی محصول — ۱ پاراگراف + bullet مزایا}

## {H2 — اشتباهات رایج}

## سوالات متداول
### سوال ۱؟
پاسخ.

## جمع‌بندی
{خلاصه + CTA}

## مطالب مرتبط
- [لینک ۱](/blog/...)
- [لینک ۲](/blog/...)

---
**CTA:** [شروع ۴۸ ساعت رایگان پوشه](https://posheapp.ir/register)
```

---

## قالب پیلار (Pillar Page)

| بخش | H2 |
|-----|-----|
| ۱ | {موضوع} چیست؟ |
| ۲ | چالش‌های دفاتر املاک ایران |
| ۳ | راه‌حل‌ها و معیار انتخاب |
| ۴ | قابلیت‌های پوشه در {موضوع} |
| ۵ | مقایسه روش سنتی vs دیجیتال |
| ۶ | گام‌های پیاده‌سازی |
| ۷ | سوالات متداول |
| ۸ | شروع رایگان |

طول: ۳۵۰۰–۵۵۰۰ کلمه · ۲۰+ لینک به cluster

لیست پیلارها: [06-Pillar-Pages.md](06-Pillar-Pages.md)

---

## قالب چک‌لیست

```markdown
# چک‌لیست {موضوع}

- [ ] مورد ۱
- [ ] مورد ۲
...

## دانلود PDF (اختیاری)
```

نمونه: [articles/property-filing-checklist.md](articles/property-filing-checklist.md)

---

## قالب مقایسه (Comparison)

```markdown
# مقایسه {A} و {B} برای املاک

| معیار | روش سنتی | پوشه |
|-------|----------|------|
| سرعت ثبت | ... | ... |
```

**توجه:** مقایسه با رقبای نام‌برده فقط با fact-check — [27-Competitor-Analysis.md](27-Competitor-Analysis.md)

---

## قالب لندینگ شهر

```
H1: CRM املاک {شهر} ۱۴۰۴
مقدمه محلی · آمار بازار · CTA تریال
```

کلمات شهر: [04-Keyword-Database.md](04-Keyword-Database.md)

---

## بلوک‌های قابل استفاده مجدد

### بلوک معرفی پوشه

> **پوشه** سامانه ابری ایرانی برای آژانس‌های املاک است: فایلینگ، CRM، حسابداری، فرم ۱۲۵، اپ اندروید و ویندوز، ربات تلگرام/واتساپ، تقویم جلالی و **۴۸ ساعت آزمایش رایگان** برای مشاوران مستقل.

### بلوک CTA میانی

---
🚀 **آماده‌اید CRM را عملی کنید؟** [۴۸ ساعت رایگان — بدون کارت](https://posheapp.ir/register)

---

## Schema یادداشت (انتهای فایل markdown)

```markdown
## یادداشت Schema
- Article + FAQPage + BreadcrumbList
- نویسنده: Organization «پوشه»
```

جزئیات: [16-Schema.md](16-Schema.md)

---

## تصویر

| # | نقش | پرامپت |
|---|-----|--------|
| 1 | Hero | [14-Image-Prompts.md](14-Image-Prompts.md) |
| 2 | اینفوگرافیک | اختیاری |

---

## انواع محتوا × قالب

| نوع | قالب | طول |
|-----|------|-----|
| راهنما | استاندارد | ۲۵۰۰+ |
| چک‌لیست | checklist | ۱۵۰۰+ |
| پیلار | pillar | ۳۵۰۰+ |
| FAQ محور | استاندارد + FAQ بلند | ۲۰۰۰+ |
| تحول دیجیتال | استاندارد + case | ۳۰۰۰+ |

---

## مقالات نمونه کامل

1. [articles/real-estate-crm-guide.md](articles/real-estate-crm-guide.md)
2. [articles/property-filing-checklist.md](articles/property-filing-checklist.md)
3. [articles/digital-transformation-agency.md](articles/digital-transformation-agency.md)

---

## لینک‌های مرتبط

- [11-SEO-Rules.md](11-SEO-Rules.md)
- [14-Image-Prompts.md](14-Image-Prompts.md)
- [15-FAQ-Database.md](15-FAQ-Database.md)
- [17-CTA.md](17-CTA.md)
- [22-Publishing-Checklist.md](22-Publishing-Checklist.md)
