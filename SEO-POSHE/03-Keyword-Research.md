# متدولوژی تحقیق کلمه کلیدی پوشه

> خروجی: [04-Keyword-Database.md](04-Keyword-Database.md) · `data/keywords.csv` · [07-Topic-Clusters.md](07-Topic-Clusters.md)

## اهداف تحقیق

1. پوشش **تمام intentهای** خرید و آموزش در حوزه نرم‌افزار و مدیریت املاک ایران
2. ساخت **خوشه‌های موضوعی** قابل اجرا برای ۴۰۰+ صفحه
3. اولویت‌بندی بر اساس **فاصله از تبدیل** و **سختی رقابت**
4. هم‌راستایی با [01-Business.md](01-Business.md) و [02-Persona.md](02-Persona.md)

---

## منابع داده

| منبع | کاربرد |
|------|--------|
| Google Search Console | کلمات واقعی با impression/click |
| Google Trends (ایران) | فصلی‌بودن · مقایسه شهرها |
| SERP دستی (fa-IR) | تحلیل ۱۰ نتیجه اول |
| رقبا ([27-Competitor-Analysis.md](27-Competitor-Analysis.md)) | شکاف محتوا |
| تیم محصول/فروش | سوالات پشتیبانی و دمو |
| اسکریپت داخلی | `scripts/generate-seo-keywords.py` |
| پایگاه ۱۴٬۸۷۰ کلمه | [`data/keywords.csv`](data/keywords.csv) |

---

## فرآیند ۶ مرحله‌ای

### مرحله ۱: Seed Keywords

از ماژول‌های محصول seed استخراج می‌شود:

```
نرم افزار املاک · CRM املاک · فایلینگ · فرم ۱۲۵ · حسابداری دفتر املاک ·
ربات تلگرام املاک · QR ملک · KPI مشاور · پورتال مالک · سامانه ابری املاک
```

### مرحله ۲: گسترش (Expansion)

هر seed با الگوهای زیر ترکیب می‌شود:

| الگو | مثال |
|------|------|
| `{کلمه} + {شهر}` | CRM املاک تهران |
| `{کلمه} + ۱۴۰۴` | نرم افزار املاک ۱۴۰۴ |
| `{کلمه} + رایگان/قیمت/آموزش` | CRM املاک رایگان |
| `{کلمه} + برای مشاور/مدیر` | فایلینگ برای مشاور |
| سوالی (چگونه/چیست/بهترین) | چگونه ملک ثبت کنیم |

### مرحله ۳: طبقه‌بندی Intent

| Intent | تعریف | نمونه | نوع محتوا |
|--------|--------|-------|-----------|
| **Informational** | یادگیری | آموزش ثبت ملک | blog · guide |
| **Commercial** | مقایسه/ارزیابی | بهترین CRM املاک | comparison · pillar |
| **Transactional** | خرید/ثبت‌نام | نرم افزار املاک خرید | landing · pricing |
| **Navigational** | برند | پوشه املاک | homepage |

### مرحله ۴: نگاشت به پیلار

هر کلمه به یک `pillar-*` در [06-Pillar-Pages.md](06-Pillar-Pages.md) نگاشت می‌شود:

- `pillar-software` · `pillar-crm` · `pillar-filing` · `pillar-contracts`
- `pillar-accounting` · `pillar-mobile` · `pillar-marketing` · …

### مرحله ۵: اولویت P1–P3

| اولویت | معیار |
|--------|--------|
| **P1** | intent تجاری/تراکنشی · حجم بالا · تطابق مستقیم محصول |
| **P2** | informational با پتانسیل لینک داخلی به P1 |
| **P3** | long-tail · شهرهای کوچک · تکمیل خوشه |

### مرحله ۶: اعتبارسنجی SERP

قبل از تولید محتوا برای هر P1:

1. جستجوی `site:posheapp.ir` — آیا cannibalization داریم؟
2. بررسی Featured Snippet و People Also Ask
3. ثبت طول محتوای رتبه ۱–۳ (هدف: ۱۰٪ بیشتر + کیفیت)
4. یادداشت نوع محتوا (ویدیو، لیست، جدول)

---

## ابعاد CSV (`data/keywords.csv`)

| ستون | توضیح |
|------|--------|
| `keyword` | عبارت فارسی |
| `intent` | informational / commercial / transactional |
| `difficulty` | low / medium / high |
| `pillar` | شناسه پیلار |
| `content_type` | blog / landing / comparison |
| `priority` | P1 / P2 / P3 |
| `city` | اختیاری — تهران، مشهد، … |

---

## خوشه‌بندی (Clustering)

```
Pillar (۱ صفحه قوی)
  └── Cluster مقالات (۱۵–۳۰)
        └── Supporting long-tail (شهر · سوال · چک‌لیست)
```

نقشه کامل: [07-Topic-Clusters.md](07-Topic-Clusters.md)

قوانین خوشه:

- هر مقاله cluster **یک کلمه اصلی** + ۲–۴ کلمه ثانویه
- لینک **همیشه** به پیلار (anchor متنوع)
- لینک **افقی** به ۲–۳ مقاله هم‌خوشه

جزئیات لینک: [09-Internal-Linking.md](09-Internal-Linking.md)

---

## کلمات ممنوع و cannibalization

| قانون | مثال |
|-------|------|
| یک URL = یک primary keyword | دو مقاله «CRM املاک» نداریم |
| صفحه پیلار = head term | «نرم افزار املاک» فقط روی پیلار |
| شهر در URL جدا | `crm-real-estate-tehran` vs `crm-real-estate-mashhad` |
| برند جدا | «پوشه» → صفحه محصول، نه وبلاگ |

---

## فصلی‌بودن (ایران)

| دوره | موضوعات پیک |
|------|-------------|
| فروردین–خرداد | رونق بازار · ثبت‌نام مشاور جدید |
| تابستان | اجاره · دانشجویی |
| پاییز | قرارداد · حسابداری سالانه |
| زمستان | برنامه‌ریزی سال بعد · KPI |

تقویم: [08-Content-Calendar.md](08-Content-Calendar.md)

---

## معیارهای موفقیت تحقیق کلمه

| معیار | هدف ۶ ماه |
|-------|-----------|
| P1 با محتوای منتشرشده | ۱۰۰٪ |
| خوشه‌های با پیلار + ۱۰ مقاله | ۱۵ خوشه |
| CTR از impression &gt; ۳٪ | top ۵۰ کلمه |
| رتبه ≤ ۱۰ | ۲۰۰+ کلمه |

اندازه‌گیری: [23-Google-Search-Console.md](23-Google-Search-Console.md)

---

## چک‌لیست قبل از نوشتن مقاله

- [ ] کلمه در CSV با priority مشخص است
- [ ] پیلار و دسته در [05-Blog-Categories.md](05-Blog-Categories.md) تعیین شده
- [ ] پرسونای هدف از [02-Persona.md](02-Persona.md) انتخاب شده
- [ ] Meta Title/Description طبق [18-Meta-Titles.md](18-Meta-Titles.md) و [19-Meta-Descriptions.md](19-Meta-Descriptions.md)
- [ ] Slug انگلیسی از [20-Slugs.md](20-Slugs.md)
- [ ] FAQ از [15-FAQ-Database.md](15-FAQ-Database.md) یا اختصاصی

---

## لینک‌های مرتبط

- [04-Keyword-Database.md](04-Keyword-Database.md)
- [06-Pillar-Pages.md](06-Pillar-Pages.md)
- [09-Internal-Linking.md](09-Internal-Linking.md)
- [11-SEO-Rules.md](11-SEO-Rules.md)
- [27-Competitor-Analysis.md](27-Competitor-Analysis.md)
