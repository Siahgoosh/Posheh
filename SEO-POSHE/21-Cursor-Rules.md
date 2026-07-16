# قوانین تولید محتوا با Cursor / AI

> برای تیم و agentها · [13-Content-Templates.md](13-Content-Templates.md) · [11-SEO-Rules.md](11-SEO-Rules.md)

## هدف

تولید یکنواخت مقالات **فارسی RTL** با کیفیت production برای وبلاگ `posheapp.ir` بدون نقض E-E-A-T یا stuffing.

---

## فایل‌های مرجع اجباری

قبل از نوشتن هر مقاله این فایل‌ها را بخوانید:

1. [01-Business.md](01-Business.md) — محصول و ارزش
2. [02-Persona.md](02-Persona.md) — مخاطب
3. [04-Keyword-Database.md](04-Keyword-Database.md) یا `data/keywords.csv`
4. [13-Content-Templates.md](13-Content-Templates.md) — ساختار
5. [11-SEO-Rules.md](11-SEO-Rules.md) — قوانین on-page
6. [15-FAQ-Database.md](15-FAQ-Database.md) — FAQ
7. [09-Internal-Linking.md](09-Internal-Linking.md) — لینک‌ها

---

## پرامپت سیستم پیشنهادی (Cursor Rule)

```
تو نویسنده محتوای سئو فارسی برای پوشه (Poshe) هستی — SaaS ابری املاک ایران.

قوانین:
- زبان: فارسی رسمی-صمیمی، RTL، نیم‌فاصله صحیح
- محصول: فایلینگ، CRM، حسابداری، فرم 125، تیم، تطبیق، وبسایت، اپ اندروید/ویندوز، ربات تلگرام/واتساپ، OTP، جلالی، QR، KPI، زیبال، 48h Solo trial
- هر مقاله: SEO Title, Meta Title, Meta Description, Slug, H1, TOC, H2/H3, FAQ, Schema notes, Image prompt+ALT, internal links, CTA
- طول: 2500+ کلمه برای راهنما
- بدون ادعای «بهترین جهان»؛ بدون قیمت رقیب بدون منبع
- لینک داخلی به پیلار و 2 مقاله مرتبط
- CTA: https://posheapp.ir/register — 48 ساعت رایگان
- Slug انگلیسی lowercase با خط تیره
- از SEO-POSHE/15-FAQ-Database.md سوال انتخاب کن
- Schema: Article + FAQPage + BreadcrumbList (یادداشت در انتها)
```

---

## workflow تولید

```
1. انتخاب کلمه از CSV (priority P1)
2. تعیین persona + pillar + category
3. outline H2 در comment
4. نوشتن بدنه
5. افزودن FAQ + CTA + لینک‌ها
6. meta + slug از 18/19/20
7. image prompt از 14
8. چک‌لیست 22
```

---

## ممنوعیت‌ها

| ❌ ممنوع | ✅ جایگزین |
|---------|-----------|
| کپی SERP بدون بازنویسی | تحلیل + تجربه + مثال ایرانی |
| hallucination قانون/قیمت | «طبق رویه رایج» + ارجاع |
| keyword stuffing | خوانایی طبیعی |
| دو مقاله با یک primary KW | یک URL — [03-Keyword-Research.md](03-Keyword-Research.md) |
| لینک به رقیب با dofollow تبلیغ | nofollow یا فقط نام |
| متن انگلیسی در body | فقط slug و URL |

---

## فرمت خروجی markdown

مطابق [articles/real-estate-crm-guide.md](articles/real-estate-crm-guide.md):

- YAML front matter کامل
- بلوک `## متادیتای سئو` در صورت نبود YAML
- `## یادداشت Schema` در انتها
- `## پرامپت تصویر` + ALT

---

## بازبینی انسانی

AI خروجی را تولید می‌کند؛ انسان قبل از انتشار:

- [ ] fact-check قوانین و اعداد
- [ ] تست لینک‌ها
- [ ] خواندن صوتی برای روان بودن
- [ ] امتیاز چک‌لیست ≥ ۸۰

---

## اسکریپت‌های کمکی

```bash
python3 scripts/generate-seo-keywords.py
python3 scripts/generate-seo-content-plan.py
```

---

## لینک‌های مرتبط

- [30-AI-Prompts.md](30-AI-Prompts.md)
- [22-Publishing-Checklist.md](22-Publishing-Checklist.md)
- [25-EEAT.md](25-EEAT.md)
- [26-Helpful-Content.md](26-Helpful-Content.md)
