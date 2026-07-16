# Google Analytics 4 — راهنمای پوشه

> رفتار کاربر و تبدیل · [23-Google-Search-Console.md](23-Google-Search-Console.md) · [17-CTA.md](17-CTA.md)

## راه‌اندازی

1. ایجاد GA4 property برای `posheapp.ir`
2. نصب gtag.js یا GTM
3. اتصال GSC به GA4 (Linking)
4. تعریف conversions

---

## رویدادهای سفارشی (Custom Events)

| Event | پارامترها | Conversion |
|-------|-----------|------------|
| `cta_click` | `cta_location`, `cta_text`, `page_path` | خیر |
| `sign_up_start` | `source`, `article_slug` | بله |
| `sign_up_complete` | `plan`, `trial` | بله |
| `trial_activated` | `hours: 48` | بله |
| `demo_request` | `type: team` | بله |
| `app_download_click` | `platform: android` | خیر |
| `scroll_depth` | `percent: 50, 90` | خیر |
| `faq_expand` | `question_id` | خیر |

---

## پارامترهای UTM استاندارد

```
?utm_source=blog
&utm_medium=organic
&utm_campaign={slug}
&utm_content=cta-mid
```

مثال:

```
https://posheapp.ir/register?utm_source=blog&utm_medium=organic&utm_campaign=real-estate-crm-guide
```

---

## گزارش‌های کلیدی

### Acquisition

- **Organic Search** → Landing page = `/blog/*`
- مقایسه با Direct و Referral

### Engagement

| متریک | هدف مقاله |
|-------|-----------|
| Average engagement time | &gt; ۳ دقیقه |
| Bounce rate | &lt; ۶۵٪ |
| Scroll 90% | &gt; ۲۵٪ |

### Monetization / Conversions

- `sign_up_complete` attributed to blog
- **هدف:** ۱۵٪ ثبت‌نام از وبلاگ ([01-Business.md](01-Business.md))

---

## Explorations پیشنهادی

### ۱. مسیر وبلاگ → ثبت‌نام

```
Landing: /blog/real-estate-crm-guide
→ register
→ trial_activated
```

### ۲. بهترین مقالات تبدیل

```
Dimension: article_slug (custom)
Metric: sign_up_complete
```

### ۳. CTA A/B

```
cta_text × cta_location → cta_click → sign_up
```

---

## Content grouping

```javascript
// در dataLayer
content_group: 'blog'
content_category: 'crm'  // از 05-Blog-Categories
pillar: 'pillar-crm'
```

---

## داشبورد Looker Studio

ویجت‌ها:

1. Organic sessions (۳۰ روز)
2. Top ۱۰ landing pages
3. Conversion rate blog → trial
4. CTA click rate by article
5. Device split (mobile %)

---

## حریم خصوصی

- IP anonymization طبق سیاست
- Cookie consent banner ایران
- عدم ارسال PII در event parameters

---

## هم‌راستایی با GSC

| GA4 | GSC |
|-----|-----|
| Organic sessions | Clicks (تقریباً) |
| Landing page | Page |
| — | Queries (فقط GSC) |

**توجه:** اعداد دقیقاً یکی نیستند — هر دو را با هم بخوانید.

---

## چک‌لیست راه‌اندازی مقاله جدید

- [ ] `page_view` با `article_slug`
- [ ] CTA events فعال
- [ ] UTM در لینک‌های CTA
- [ ] DebugView تست قبل از انتشار

---

## لینک‌های مرتبط

- [17-CTA.md](17-CTA.md)
- [22-Publishing-Checklist.md](22-Publishing-Checklist.md)
- [29-Roadmap.md](29-Roadmap.md)
