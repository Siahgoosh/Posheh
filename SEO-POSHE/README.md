# SEO-POSHE — پایگاه دانش سئو پوشه

پروژه سئو سازمانی برای تبدیل **پوشه (Poshe)** به بزرگ‌ترین مرجع فارسی نرم‌افزار و مدیریت املاک در ایران.

## ساختار

| فایل | موضوع |
|------|--------|
| [01-Business.md](01-Business.md) | مدل کسب‌وکار و ارزش پیشنهادی |
| [02-Persona.md](02-Persona.md) | پرسونای مخاطب |
| [03-Keyword-Research.md](03-Keyword-Research.md) | متدولوژی تحقیق کلمه |
| [04-Keyword-Database.md](04-Keyword-Database.md) | **۱۴٬۸۷۰+** کلمه کلیدی |
| [05-Blog-Categories.md](05-Blog-Categories.md) | ۱۵ دسته · ۵۲۵+ ایده |
| [06-Pillar-Pages.md](06-Pillar-Pages.md) | ۳۰ صفحه پیلار |
| [07-Topic-Clusters.md](07-Topic-Clusters.md) | خوشه‌های موضوعی |
| [08-Content-Calendar.md](08-Content-Calendar.md) | تقویم ۱۲ ماهه |
| [09-Internal-Linking.md](09-Internal-Linking.md) | لینک‌سازی داخلی |
| [10-URL-Structure.md](10-URL-Structure.md) | ساختار URL |
| [11-SEO-Rules.md](11-SEO-Rules.md) | قوانین محتوا |
| [12-Technical-SEO.md](12-Technical-SEO.md) | سئو فنی |
| [13-Content-Templates.md](13-Content-Templates.md) | قالب مقاله |
| [14-Image-Prompts.md](14-Image-Prompts.md) | پرامپت تصویر |
| [15-FAQ-Database.md](15-FAQ-Database.md) | بانک FAQ |
| [16-Schema.md](16-Schema.md) | JSON-LD |
| [17-CTA.md](17-CTA.md) | فراخوان اقدام |
| [18-Meta-Titles.md](18-Meta-Titles.md) | عنوان متا |
| [19-Meta-Descriptions.md](19-Meta-Descriptions.md) | توضیح متا |
| [20-Slugs.md](20-Slugs.md) | اسلاگ انگلیسی |
| [21-Cursor-Rules.md](21-Cursor-Rules.md) | قوانین تولید با AI |
| [22-Publishing-Checklist.md](22-Publishing-Checklist.md) | چک‌لیست انتشار |
| [23-Google-Search-Console.md](23-Google-Search-Console.md) | GSC |
| [24-Google-Analytics.md](24-Google-Analytics.md) | GA4 |
| [25-EEAT.md](25-EEAT.md) | تخصص و اعتماد |
| [26-Helpful-Content.md](26-Helpful-Content.md) | محتوای مفید گوگل |
| [27-Competitor-Analysis.md](27-Competitor-Analysis.md) | رقبا |
| [28-Link-Building.md](28-Link-Building.md) | لینک‌سازی |
| [29-Roadmap.md](29-Roadmap.md) | نقشه راه |
| [30-AI-Prompts.md](30-AI-Prompts.md) | پرامپت‌های AI |

## داده خام

- [`data/keywords.csv`](data/keywords.csv) — ۱۴٬۸۷۰ کلمه کلیدی با intent، pillar، difficulty

## اسکریپت‌ها

```bash
python3 scripts/generate-seo-keywords.py
python3 scripts/generate-seo-content-plan.py
```

## اجرا در محصول

- وبلاگ: `https://posheapp.ir/blog`
- سایت‌مپ پویا: `https://posheapp.ir/sitemap.xml`
- robots: `https://posheapp.ir/robots.txt`

## اهداف KPI (۱۲ ماه)

| شاخص | هدف |
|------|-----|
| صفحات ایندکس | ۴۰۰+ |
| کلیک ارگانیک ماهانه | ۵۰٬۰۰۰+ |
| کلمات Top 10 | ۲۰۰+ |
| ثبت‌نام از وبلاگ | ۱۵٪ از کل |

---

**دامنه:** posheapp.ir · **زبان:** fa-IR · **RTL:** بله
