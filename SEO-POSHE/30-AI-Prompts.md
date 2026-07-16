# پرامپت‌های AI برای تولید محتوا

> قابل کپی در ChatGPT · Claude · Cursor · [21-Cursor-Rules.md](21-Cursor-Rules.md)

---

## پرامپت ۱: مقاله کامل وبلاگ

```
تو نویسنده ارشد محتوای فارسی برای پوشه (Poshe) هستی — SaaS ابری نرم‌افزار املاک ایران.

محصول: فایلینگ، CRM، حسابداری، فرم 125، تیم، تطبیق ملک-مشتری، وبسایت آژانس، اپ Android/Windows، ربات Telegram/WhatsApp، OTP، تقویم جلالی، QR ملک، گزارش KPI، پرداخت زیبال، 48 ساعت تریال Solo رایگان.

موضوع: {TOPIC}
کلمه اصلی: {PRIMARY_KEYWORD}
پرسونا: {PERSONA از 02-Persona}
پیلار: {PILLAR_ID}
Slug: {english-slug}

خروجی markdown با:
- YAML front matter (seo_title, meta_title, meta_description, slug, keywords, pillar, category, persona)
- H1، فهرست مطالب، 6-8 H2 با H3 در صورت نیاز
- حداقل 2500 کلمه فارسی RTL
- یک جدول و یک لیست شماره‌دار
- بخش «نقش پوشه» طبیعی (نه تبلیغ هر پاراگراف)
- 5 سوال FAQ با پاسخ 40-80 کلمه
- بلوک مطالب مرتبط (3 لینک داخلی فرضی /blog/...)
- CTA: https://posheapp.ir/register — 48 ساعت رایگان
- پرامپت تصویر hero + ALT فارسی
- یادداشت Schema: Article + FAQPage + BreadcrumbList

قوانین: بدون keyword stuffing؛ مثال شهر ایران؛ نیم‌فاصله؛ بدون ادعای «بهترین جهان».
```

---

## پرامپت ۲: Meta Title + Description

```
برای مقاله پوشه با کلمه اصلی «{KEYWORD}» بنویس:
1. Meta Title (حداکثر 60 کاراکتر، برند «پوشه» در انتها)
2. Meta Description (140-160 کاراکتر، مزیت 48h رایگان، CTA)

از الگوهای 18-Meta-Titles.md و 19-Meta-Descriptions.md پیروی کن.
```

---

## پرامپت ۳: FAQ اختصاصی

```
برای موضوع «{TOPIC}» و محصول پوشه، 8 سوال FAQ بنویس که در 15-FAQ-Database تکراری نباشند.
فرمت: ### سوال؟ + پاسخ 40-100 کلمه.
مخاطب: {PERSONA}
```

---

## پرامپت ۴: بازنویسی برای CTR

```
این Meta Title و Description CTR پایین در GSC دارند:
Title: {OLD_TITLE}
Description: {OLD_DESCRIPTION}
Query اصلی: {QUERY}

3 جایگزین A/B بنویس با قوانین 18 و 19. فارسی. پوشه.
```

---

## پرامپت ۵: پاراگراف محلی (شهر)

```
یک پاراگراف 150 کلمه برای بخش «{TOPIC} در {CITY}» بنویس.
بازار املاک {CITY} را واقع‌بینانه ذکر کن (بدون آمار جعلی).
لینک طبیعی به پوشه و CRM.
```

---

## پرامپت ۶: outline پیلار

```
outline صفحه پیلار برای «{PILLAR_TOPIC}» با 8 H2 و 3 bullet زیر هر H2.
هدف: 4000 کلمه، 20 لینک به cluster.
مرجع ساختار: 06-Pillar-Pages.md
```

---

## پرامپت ۷: Image prompt

```
برای مقاله «{TITLE}» یک پرامپت تصویر hero و ALT فارسی بنویس.
سبک: minimal flat vector, blue #2563EB, Iranian real estate office, 1200x630, no text.
از 14-Image-Prompts.md پیروی کن.
```

---

## پرامپت ۸: Schema JSON-LD

```
JSON-LD @graph برای مقاله:
URL: https://posheapp.ir/blog/{slug}
Title: {TITLE}
Date: {DATE}
3 FAQ از متن زیر:
{FAQ_TEXT}

شامل Article, FAQPage, BreadcrumbList (خانه > وبلاگ > دسته > عنوان).
```

---

## پرامپت ۹: لینک داخلی

```
برای مقاله با slug «{SLUG}» در خوشه «{CLUSTER}» پیشنهاد بده:
- 1 anchor به پیلار
- 3 anchor به مقالات هم‌خوشه
- 1 CTA anchor

طبق 09-Internal-Linking.md
```

---

## پرامپت ۱۰: بازبینی E-E-A-T

```
این مقاله را برای E-E-A-T نمره 0-10 بده و 5 پیشنهاد مشخص بهبود بده:
{ARTICLE_TEXT}

مرجع: 25-EEAT.md و 26-Helpful-Content.md
```

---

## پرامپت ۱۱: خلاصه برای شبکه اجتماعی

```
از مقاله «{TITLE}» یک پست تلگرام 200 کلمه + 3 هشتگ فارسی + لینک /blog/{slug}
```

---

## پرامپت ۱۲: مقایسه با اکسل

```
جدول مقایسه 8 ردیف «مدیریت املاک با اکسل» vs «پوشه SaaS».
ستون‌ها: معیار | اکسل | پوشه
بدون حمله به برند رقیب. فارسی.
```

---

## متغیرهای محیطی

| متغیر | منبع |
|-------|------|
| TOPIC | 08-Content-Calendar |
| KEYWORD | data/keywords.csv |
| PERSONA | 02-Persona |
| PILLAR | 06-Pillar-Pages |
| SLUG | 20-Slugs |

---

## لینک‌های مرتبط

- [21-Cursor-Rules.md](21-Cursor-Rules.md)
- [13-Content-Templates.md](13-Content-Templates.md)
- [22-Publishing-Checklist.md](22-Publishing-Checklist.md)
- مقالات نمونه: `articles/`
