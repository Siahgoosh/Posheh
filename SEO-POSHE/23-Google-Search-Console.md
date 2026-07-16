# Google Search Console — راهنمای پوشه

> اندازه‌گیری ایندکس و عملکرد ارگانیک · [24-Google-Analytics.md](24-Google-Analytics.md)

## راه‌اندازی

1. ورود به [Google Search Console](https://search.google.com/search-console)
2. افزودن property: **Domain** `posheapp.ir` (ترجیح) یا URL-prefix
3. تأیید DNS TXT یا HTML
4. ارسال sitemap: `https://posheapp.ir/sitemap.xml`

---

## sitemapهای پیشنهادی

```
https://posheapp.ir/sitemap.xml
https://posheapp.ir/sitemap-blog.xml
https://posheapp.ir/sitemap-pillars.xml
```

جزئیات: [10-URL-Structure.md](10-URL-Structure.md)

---

## گزارش‌های کلیدی

### Performance (عملکرد)

| متریک | کاربرد | هدف ۱۲ ماه |
|-------|--------|------------|
| Total clicks | ترافیک ارگانیک | ۵۰K+/ماه |
| Total impressions | دیده‌شدن | رشد ۲۰٪ QoQ |
| Average CTR | کیفیت title/desc | &gt; ۳٪ میانگین |
| Average position | رتبه | ≤ ۱۵ میانگین P1 |

**فیلترها:**

- صفحه: `/blog/`
- کشور: ایران
- دستگاه: Mobile vs Desktop
- Query: کلمات P1 از [04-Keyword-Database.md](04-Keyword-Database.md)

### Indexing (صفحه‌بندی)

- **Indexed** vs **Not indexed** — هدف ۴۰۰+ indexed
- بررسی هفتگی: `Crawled - currently not indexed`
- `Duplicate without user-selected canonical` → fix canonical

### Pages (Core Web Vitals)

- URLهای Poor → اولویت با [12-Technical-SEO.md](12-Technical-SEO.md)

### Links

- Top linked pages — باید پیلارها باشند ([09-Internal-Linking.md](09-Internal-Linking.md))
- External links — گزارش ماهانه [28-Link-Building.md](28-Link-Building.md)

---

## workflow هفتگی

| روز | کار |
|-----|-----|
| دوشنبه | Performance ۷ روز — CTR افت‌یافته |
| چهارشنبه | Coverage — خطاهای جدید |
| جمعه | Top ۲۰ query — فرصت محتوا |

---

## اقدام بر اساس Query

| وضعیت | اقدام |
|-------|--------|
| impression بالا · CTR پایین | بازنویسی Meta ([18](18-Meta-Titles.md) · [19](19-Meta-Descriptions.md)) |
| position ۱۱–۲۰ | تقویت لینک داخلی + به‌روزرسانی محتوا |
| position ۴–۱۰ | FAQ + Schema ([16-Schema.md](16-Schema.md)) |
| query بدون صفحه | مقاله جدید در [08-Content-Calendar.md](08-Content-Calendar.md) |

---

## URL Inspection

برای هر مقاله P1 جدید:

1. Inspect URL
2. Request indexing (با احتیاط — فقط کیفیت بالا)
3. بررسی `Referring page` برای کراول

---

## گزارش‌های سفارشی (Looker / export)

```
Dimensions: query, page, device
Metrics: clicks, impressions, ctr, position
Filter: page contains /blog/
```

---

## هشدارها (Alerts)

- ایمیل GSC برای: manual action، coverage spike، security
- Slack webhook برای افت &gt; ۳۰٪ click هفتگی

---

## cannibalization

جستجو در Performance:

```
دو URL برای یک query مشابه
```

→ ادغام محتوا یا تفکیک intent — [03-Keyword-Research.md](03-Keyword-Research.md)

---

## چک‌لیست ماهانه

- [ ] همه sitemap بدون خطا
- [ ] ۰ manual action
- [ ] CWV Good &gt; ۷۵٪ URL
- [ ] گزارش top ۵۰ query به تیم محتوا
- [ ] به‌روزرسانی [29-Roadmap.md](29-Roadmap.md)

---

## لینک‌های مرتبط

- [12-Technical-SEO.md](12-Technical-SEO.md)
- [24-Google-Analytics.md](24-Google-Analytics.md)
- [22-Publishing-Checklist.md](22-Publishing-Checklist.md)
