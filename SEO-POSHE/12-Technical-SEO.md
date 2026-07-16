# سئو فنی پوشه (Technical SEO)

> پیاده‌سازی در `posheapp.ir` · مکمل [10-URL-Structure.md](10-URL-Structure.md) · [16-Schema.md](16-Schema.md)

## چک‌لیست فنی سطح A

### کراول و ایندکس

- [ ] `robots.txt` اجازه `/blog` و `/features`
- [ ] `sitemap.xml` پویا · `lastmod` دقیق
- [ ] ثبت sitemap در GSC ([23-Google-Search-Console.md](23-Google-Search-Console.md))
- [ ] بدون `noindex` تصادفی روی مقالات
- [ ] canonical خودکار روی همه صفحات
- [ ] پوشش ایندکس هدف: ۴۰۰+ URL در ۱۲ ماه

### عملکرد (Core Web Vitals)

| متریک | هدف موبایل |
|-------|------------|
| LCP | &lt; ۲.۵ ثانیه |
| INP | &lt; ۲۰۰ ms |
| CLS | &lt; ۰.۱ |

**اقدامات:**

- CDN برای استاتیک (ایران + خارج)
- WebP/AVIF برای تصاویر وبلاگ
- فونت فارسی subset (Vazirmatn / IRANSans) با `font-display: swap`
- Critical CSS برای above-fold
- defer/async برای JS غیرضروری

### موبایل

- Responsive · viewport صحیح
- دکمه‌های CTA حداقل ۴۸×۴۸ px
- تست RTL در iOS Safari و Chrome Android
- اپ اندروید پوشه — لینک `intent://` یا deep link از وبلاگ

---

## HTML و Meta پایه

```html
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>راهنمای CRM املاک ۱۴۰۴ | پوشه</title>
  <meta name="description" content="..." />
  <link rel="canonical" href="https://posheapp.ir/blog/real-estate-crm-guide" />
  <meta property="og:locale" content="fa_IR" />
  <meta property="og:type" content="article" />
  <meta property="og:title" content="..." />
  <meta property="og:description" content="..." />
  <meta property="og:image" content="https://posheapp.ir/og/real-estate-crm-guide.webp" />
  <meta name="twitter:card" content="summary_large_image" />
</head>
```

---

## Open Graph و اشتراک‌گذاری

| فیلد | مقدار |
|------|--------|
| `og:locale` | `fa_IR` |
| `og:site_name` | پوشه |
| تصویر OG | ۱۲۰۰×۶۳۰ · برند + عنوان |
| `article:published_time` | ISO 8601 |
| `article:author` | نام نویسنده |

---

## Structured Data

انواع اصلی: `Article` · `FAQPage` · `BreadcrumbList` · `SoftwareApplication` (صفحات محصول)

جزئیات JSON-LD: [16-Schema.md](16-Schema.md)

اعتبارسنجی: [Google Rich Results Test](https://search.google.com/test/rich-results)

---

## امنیت و اعتماد

| مورد | وضعیت |
|------|--------|
| HTTPS / TLS 1.2+ | اجباری |
| HSTS | توصیه‌شده |
| CSP | تدریجی |
| OTP ورود | محصول |
| حریم خصوصی | `/privacy` لینک در فوتر |

---

## بین‌المللی‌سازی و RTL

```css
html[dir="rtl"] {
  text-align: right;
}
```

- `dir="rtl"` روی `<html>`
- آیکون‌های جهت‌دار (فلش) mirror شوند
- جداول عددی: `direction: ltr` برای ستون قیمت در صورت نیاز

---

## لاگ و مانیتورینگ

| ابزار | کاربرد |
|-------|--------|
| Google Search Console | ایندکس · CWV · خطا |
| GA4 | رفتار · تبدیل ([24-Google-Analytics.md](24-Google-Analytics.md)) |
| uptime monitor | 99.9٪ |
| log سرور | ۵xx · crawl budget |

### هشدارهای بحرانی

- افزایش ناگهانی ۴۰۴ در `/blog`
- افت &gt; ۲۰٪ impression هفتگی
- CWV «Poor» در &gt; ۲۵٪ URL

---

## SPA / SSR (اگر React/Next)

- **SSR یا SSG** برای `/blog/*` — محتوا در HTML اولیه
- Meta tags سرور-ساید
- JSON-LD در response HTML
- جلوگیری از soft 404 (صفحه خالی با ۲۰۰)

---

## pagination و آرشیو

```
/blog/category/crm?page=2
```

- `rel="next"` / `rel="prev"` یا canonical به صفحه ۱ با view all
- meta robots `noindex,follow` برای صفحه ۲+ (اختیاری سیاست)

---

## تصاویر فنی

```html
<img
  src="/images/blog/real-estate-crm-guide-hero.webp"
  alt="داشبورد CRM املاک پوشه با لیست مشتریان و املاک"
  width="1200"
  height="630"
  loading="lazy"
  decoding="async"
/>
```

- `srcset` برای responsive
- explicit width/height برای CLS

---

## preconnect

```html
<link rel="preconnect" href="https://cdn.posheapp.ir" />
```

---

## لینک‌های مرتبط

- [10-URL-Structure.md](10-URL-Structure.md)
- [16-Schema.md](16-Schema.md)
- [23-Google-Search-Console.md](23-Google-Search-Console.md)
- [22-Publishing-Checklist.md](22-Publishing-Checklist.md)
