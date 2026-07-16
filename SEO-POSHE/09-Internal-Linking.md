# استراتژی لینک‌سازی داخلی پوشه

> مکمل [07-Topic-Clusters.md](07-Topic-Clusters.md) · [10-URL-Structure.md](10-URL-Structure.md) · [06-Pillar-Pages.md](06-Pillar-Pages.md)

## اهداف

1. توزیع **PageRank داخلی** به صفحات پیلار و لندینگ
2. کمک به **کراول و ایندکس** مقالات جدید
3. افزایش **زمان ماندگاری** و مسیر تبدیل
4. جلوگیری از **cannibalization** کلمات کلیدی

---

## معماری لینک (Hub & Spoke)

```
صفحه اصلی (posheapp.ir)
    ├── صفحات محصول (/features/*)
    ├── صفحات پیلار (/blog/pillar/*)  ← HUB
    │       ├── مقالات خوشه (/blog/{slug})
    │       └── دسته‌ها (/blog/category/*)
    └── مقالات نمونه (articles/)
```

### قانون طلایی

> هر مقاله وبلاگ: **۱ لینک به پیلار** + **۲–۴ لینک افقی** + **۱ لینک CTA محصول**

---

## انواع لینک داخلی

| نوع | جهت | Anchor Text | تعداد |
|-----|------|-------------|--------|
| **Vertical** | مقاله → پیلار | کلمه هدف پیلار (متنوع) | ۱ |
| **Horizontal** | مقاله ↔ مقاله | long-tail · عنوان H2 | ۲–۴ |
| **Category** | مقاله → دسته | نام دسته | ۰–۱ |
| **Product** | هر جا → ثبت‌نام/ویژگی | برند + مزیت | ۱–۲ |
| **Breadcrumb** | خودکار | ساختار | خودکار |

---

## Anchor Text — قوانین

### مجاز (متنوع)

- «راهنمای کامل CRM املاک»
- «سیستم فایلینگ پوشه»
- «ثبت ملک با QR»
- «شروع ۴۸ ساعت رایگان»

### ممنوع

- تکرار دقیق یک anchor در ۱۰+ مقاله برای یک URL
- لینک با «اینجا کلیک کنید» بدون context
- لینک به صفحه‌ای با intent متفاوت (مثلاً مقاله آموزشی → صفحه قیمت با anchor آموزشی)

---

## نقشه لینک پیشنهادی به پیلارها

| پیلار | URL | مقالات منبع لینک |
|-------|-----|------------------|
| نرم افزار املاک | `/blog/pillar/نرم-افزار-املاک` | software، digital، cloud |
| CRM املاک | `/blog/pillar/crm-املاک` | crm، team، visits |
| فایلینگ | `/blog/pillar/ثبت-ملک-فایلینگ` | filing، mobile، QR |
| قرارداد | `/blog/pillar/قرارداد-املاک` | contracts، form-125 |
| حسابداری | `/blog/pillar/حسابداری-دفتر-املاک` | accounting، commission |

لیست کامل: [06-Pillar-Pages.md](06-Pillar-Pages.md)

---

## لینک‌های بین مقالات نمونه

| مقاله | لینک به | Anchor پیشنهادی |
|--------|---------|-----------------|
| [real-estate-crm-guide](articles/real-estate-crm-guide.md) | property-filing-checklist | چک‌لیست ثبت ملک |
| [real-estate-crm-guide](articles/real-estate-crm-guide.md) | pillar-crm | CRM املاک |
| [property-filing-checklist](articles/property-filing-checklist.md) | real-estate-crm-guide | مدیریت مشتری در CRM |
| [digital-transformation-agency](articles/digital-transformation-agency.md) | real-estate-crm-guide | پیاده‌سازی CRM |
| همه مقالات | `/register` | ۴۸ ساعت رایگان پوشه |

---

## بلوک‌های لینک استاندارد در مقاله

### بلوک «مطالب مرتبط» (پایان مقاله)

```markdown
## مطالب مرتبط
- [راهنمای CRM املاک](/blog/real-estate-crm-guide)
- [چک‌لیست ثبت ملک](/blog/property-filing-checklist)
- [صفحه پیلار CRM املاک](/blog/pillar/crm-املاک)
```

### بلوک میانی (In-content)

بعد از H2 اول یا قبل از FAQ — یک جمله طبیعی + لینک:

> برای ثبت استاندارد ملک، [چک‌لیست فایلینگ پوشه](/blog/property-filing-checklist) را ببینید.

---

## لینک از صفحات غیروبلاگ

| صفحه | باید لینک دهد به |
|------|------------------|
| صفحه اصلی | ۳ پیلار برتر + ۳ مقاله اخیر |
| /features/crm | پیلار CRM + مقاله CRM guide |
| /features/filing | چک‌لیست فایلینگ |
| /pricing | FAQ قیمت + مقایسه |
| فوتر سایت | ۵ پیلار + وبلاگ |

---

## تعداد لینک در هر صفحه

| نوع صفحه | لینک داخلی بدنه | کل (با منو/فوتر) |
|----------|-----------------|------------------|
| مقاله ۲۰۰۰ کلمه | ۵–۸ | ≤ ۱۵ |
| پیلار ۴۰۰۰ کلمه | ۱۵–۲۵ | ≤ ۳۵ |
| لندینگ | ۳–۵ | ≤ ۱۰ |

---

## ریدایرکت و نگهداری

- تغییر slug → **۳۰۱** دائم ([10-URL-Structure.md](10-URL-Structure.md))
- حذف مقاله → ادغام با مقاله نزدیک‌تر + ۳۰۱
- به‌روزرسانی سالانه: لینک‌های شکسته ماهانه در GSC

---

## اندازه‌گیری

| متریک | ابزار | هدف |
|-------|-------|-----|
| صفحات با ۰ لینک ورودی داخلی | Screaming Frog / crawl | ۰ |
| عمق کلیک از خانه | ≤ ۳ | |
| Top linked URLs | GSC Links | پیلارها در top ۵ |

---

## چک‌لیست قبل از انتشار

- [ ] لینک به پیلار مربوطه وجود دارد
- [ ] حداقل ۲ لینک افقی به مقالات هم‌خوشه
- [ ] CTA به `/register` یا صفحه ویژگی
- [ ] همه لینک‌ها ۲۰۰ OK (بدون زنجیره ریدایرکت)
- [ ] `rel` فقط برای لینک خارجی/Ugc

---

## لینک‌های مرتبط

- [10-URL-Structure.md](10-URL-Structure.md)
- [11-SEO-Rules.md](11-SEO-Rules.md)
- [13-Content-Templates.md](13-Content-Templates.md)
- [22-Publishing-Checklist.md](22-Publishing-Checklist.md)
