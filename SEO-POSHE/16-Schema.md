# Schema Markup (JSON-LD) پوشه

> اعتبارسنجی: [Rich Results Test](https://search.google.com/test/rich-results) · [12-Technical-SEO.md](12-Technical-SEO.md)

## انواع Schema به تفکیک صفحه

| صفحه | Schema |
|------|--------|
| مقاله وبلاگ | `Article` + `FAQPage` + `BreadcrumbList` |
| پیلار | `Article` + `FAQPage` + `BreadcrumbList` |
| صفحه محصول/ویژگی | `SoftwareApplication` + `FAQPage` |
| صفحه اصلی | `Organization` + `WebSite` + `SoftwareApplication` |
| FAQ اختصاصی | `FAQPage` |

---

## Article (مقاله وبلاگ)

```json
{
  "@context": "https://schema.org",
  "@type": "Article",
  "headline": "راهنمای کامل CRM املاک برای آژانس‌ها ۱۴۰۴",
  "description": "CRM املاک چیست و چگونه سرنخ و مشتری را مدیریت کنید؟",
  "image": "https://posheapp.ir/og/real-estate-crm-guide.webp",
  "author": {
    "@type": "Organization",
    "name": "پوشه",
    "url": "https://posheapp.ir"
  },
  "publisher": {
    "@type": "Organization",
    "name": "پوشه",
    "logo": {
      "@type": "ImageObject",
      "url": "https://posheapp.ir/logo.png"
    }
  },
  "datePublished": "2026-07-21",
  "dateModified": "2026-07-21",
  "mainEntityOfPage": {
    "@type": "WebPage",
    "@id": "https://posheapp.ir/blog/real-estate-crm-guide"
  },
  "inLanguage": "fa-IR",
  "articleSection": "CRM",
  "keywords": ["CRM املاک", "مدیریت مشتری املاک", "نرم افزار CRM"]
}
```

---

## FAQPage

```json
{
  "@context": "https://schema.org",
  "@type": "FAQPage",
  "mainEntity": [
    {
      "@type": "Question",
      "name": "CRM املاک چیست؟",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "CRM املاک سیستمی برای ثبت سرنخ، مشتری، مالک، تاریخچه تماس و تبدیل به قرارداد در آژانس املاک است."
      }
    },
    {
      "@type": "Question",
      "name": "آیا پوشه برای مشاور تک‌نفره مناسب است؟",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "بله. پوشه ۴۸ ساعت آزمایش رایگان با تمام امکانات برای مشاوران مستقل دارد."
      }
    }
  ]
}
```

سوالات: [15-FAQ-Database.md](15-FAQ-Database.md)

---

## BreadcrumbList

```json
{
  "@context": "https://schema.org",
  "@type": "BreadcrumbList",
  "itemListElement": [
    {
      "@type": "ListItem",
      "position": 1,
      "name": "خانه",
      "item": "https://posheapp.ir"
    },
    {
      "@type": "ListItem",
      "position": 2,
      "name": "وبلاگ",
      "item": "https://posheapp.ir/blog"
    },
    {
      "@type": "ListItem",
      "position": 3,
      "name": "CRM",
      "item": "https://posheapp.ir/blog/category/crm"
    },
    {
      "@type": "ListItem",
      "position": 4,
      "name": "راهنمای CRM املاک",
      "item": "https://posheapp.ir/blog/real-estate-crm-guide"
    }
  ]
}
```

---

## SoftwareApplication (صفحه محصول)

```json
{
  "@context": "https://schema.org",
  "@type": "SoftwareApplication",
  "name": "پوشه",
  "alternateName": "Poshe",
  "applicationCategory": "BusinessApplication",
  "operatingSystem": "Web, Android, Windows",
  "offers": {
    "@type": "Offer",
    "price": "0",
    "priceCurrency": "IRR",
    "description": "۴۸ ساعت آزمایش رایگان"
  },
  "description": "سامانه ابری ایرانی برای فایلینگ، CRM، حسابداری و مدیریت آژانس املاک",
  "url": "https://posheapp.ir",
  "inLanguage": "fa-IR",
  "featureList": [
    "فایلینگ ملک با QR",
    "CRM املاک",
    "حسابداری دفتر",
    "فرم ۱۲۵",
    "ربات تلگرام و واتساپ",
    "تقویم جلالی",
    "گزارش KPI"
  ],
  "aggregateRating": {
    "@type": "AggregateRating",
    "ratingValue": "4.8",
    "reviewCount": "120"
  }
}
```

> `aggregateRating` فقط با داده واقعی و سیاست گوگل — در غیر این صورت حذف شود.

---

## Organization + WebSite (صفحه اصلی)

```json
{
  "@context": "https://schema.org",
  "@type": "Organization",
  "name": "پوشه",
  "url": "https://posheapp.ir",
  "logo": "https://posheapp.ir/logo.png",
  "sameAs": [
    "https://t.me/posheapp",
    "https://instagram.com/posheapp"
  ]
}
```

```json
{
  "@context": "https://schema.org",
  "@type": "WebSite",
  "name": "پوشه",
  "url": "https://posheapp.ir",
  "potentialAction": {
    "@type": "SearchAction",
    "target": "https://posheapp.ir/blog?q={search_term_string}",
    "query-input": "required name=search_term_string"
  }
}
```

---

## HowTo (چک‌لیست مقالات)

برای [property-filing-checklist](articles/property-filing-checklist.md):

```json
{
  "@context": "https://schema.org",
  "@type": "HowTo",
  "name": "چک‌لیست ثبت ملک در آژانس املاک",
  "description": "گام‌های استاندارد فایلینگ ملک با پوشه",
  "step": [
    {
      "@type": "HowToStep",
      "name": "جمع‌آوری اطلاعات پایه",
      "text": "آدرس، متراژ، قیمت، نوع معامله و مالک را ثبت کنید."
    },
    {
      "@type": "HowToStep",
      "name": "عکس‌برداری",
      "text": "حداقل ۵ عکس با نور کافی از اپ اندروید آپلود کنید."
    }
  ]
}
```

---

## قوانین پیاده‌سازی

1. JSON-LD در `<script type="application/ld+json">` — ترجیحاً یک آرایه `@graph`
2. بدون markup گمراه‌کننده ([25-EEAT.md](25-EEAT.md))
3. `dateModified` با به‌روزرسانی واقعی مقاله
4. FAQ فقط سوالاتی که در صفحه **قابل مشاهده** هستند
5. تست بعد از هر deploy

---

## نمونه @graph ترکیبی

```json
{
  "@context": "https://schema.org",
  "@graph": [
    { "@type": "Article", "headline": "..." },
    { "@type": "FAQPage", "mainEntity": [] },
    { "@type": "BreadcrumbList", "itemListElement": [] }
  ]
}
```

---

## لینک‌های مرتبط

- [15-FAQ-Database.md](15-FAQ-Database.md)
- [10-URL-Structure.md](10-URL-Structure.md)
- [13-Content-Templates.md](13-Content-Templates.md)
- [22-Publishing-Checklist.md](22-Publishing-Checklist.md)
