# سند انتقال پروژه پوشه (Posheh) — راهنمای کامل برای ادامه کار با Cursor

> **نسخه سند:** ۱۴۰۴/۰۵/۱۷ (۲۰۲۶-۰۸-۰۷)  
> **ریپوزیتوری:** https://github.com/Siahgoosh/Posheh  
> **دامنه production:** https://posheapp.ir  
> **سرور:** همان سرور قبلی — مسیر `/var/www/posheh`

---

## چطور از این سند استفاده کنی؟

اگر این پروژه را از مالک قبلی تحویل گرفته‌ای:

1. ریپوی GitHub را به **Cursor** وصل کن (Connect GitHub → انتخاب `Siahgoosh/Posheh`).
2. در چت Cursor بنویس:

   ```
   فایل docs/CURSOR-HANDOFF-FA.md را بخوان و کامل بفهم داستان پروژه چی بوده.
   فایل docs/CURSOR-AGENT-CHANGELOG-FA.md را برای آخرین تغییرات این دوره بخوان.
   سرور همان قبلی است (/var/www/posheh). از همین‌جا ادامه بده.
   ```

3. Cursor باید این سند را بخواند و بدون نیاز به تاریخچه چت قبلی، کل زمینه را بفهمد.

**این سند منبع حقیقت (Source of Truth) برای انتقال پروژه است.** اگر چیزی در چت‌های قدیمی با این سند فرق داشت، اولویت با این فایل و کد روی GitHub است.

---

## فهرست

1. [خلاصه اجرایی](#۱-خلاصه-اجرایی)
2. [محصول و مخاطب](#۲-محصول-و-مخاطب)
3. [معماری و استک فنی](#۳-معماری-و-استک-فنی)
4. [ساختار ریپو](#۴-ساختار-ریپو)
5. [دامنه‌ها و زیرساخت](#۵-دامنه‌ها-و-زیرساخت)
6. [سرور production](#۶-سرور-production)
7. [وضعیت Git و Pull Requestها](#۷-وضعیت-git-و-pull-requestها)
8. [تاریخچه کارها (از مکالمات Cursor)](#۸-تاریخچه-کارها-از-مکالمات-cursor)
9. [قابلیت‌های پیاده‌سازی‌شده](#۹-قابلیت‌های-پیاده‌سازی‌شده)
10. [پلن‌ها و تعرفه](#۱۰-پلن‌ها-و-تعرفه)
11. [باگ‌های گزارش‌شده و وضعیت رفع](#۱۱-باگ‌های-گزارش‌شده-و-وضعیت-رفع)
12. [متغیرهای محیطی مهم](#۱۲-متغیرهای-محیطی-مهم)
13. [استقرار (Deploy)](#۱۳-استقرار-deploy)
14. [حساب‌های دمو](#۱۴-حساب‌های-دمو)
15. [نقشه فایل‌های کلیدی](#۱۵-نقشه-فایل‌های-کلیدی)
16. [مستندات جانبی](#۱۶-مستندات-جانبی)
17. [کارهای باز و اولویت‌ها](#۱۷-کارهای-باز-و-اولویت‌ها)
18. [دستورالعمل برای Cursor Agent](#۱۸-دستورالعمل-برای-cursor-agent)
19. [چک‌لیست تحویل](#۱۹-چک‌لیست-تحویل)

---

## ۱. خلاصه اجرایی

**پوشه (Posheh)** یک پلتفرم SaaS چندمستاجری (multi-tenant) برای مدیریت املاک و فایلینگ دفاتر املاک ایرانی است.

| مورد | مقدار |
|------|--------|
| نام محصول | پوشه — Posheh |
| نوع | SaaS ابری، چند دفتره |
| زبان UI | فارسی RTL |
| تقویم | شمسی (جلالی) |
| احراز هویت | OTP موبایل + Sanctum |
| پرداخت | زیبال، کیف پول داخلی، کافه‌بازار (IAP) |
| دامنه اصلی | `posheapp.ir` |
| پنل ادمین پلتفرم | `panel.posheapp.ir` |
| وبسایت دفاتر | `{name}.posheapp.ir` |
| ریپو | `github.com/Siahgoosh/Posheh` |
| مسیر سرور | `/var/www/posheh` |

### نکته بحرانی (آخرین به‌روزرسانی)

**برای deploy یکجا همه فیچرهای جدید (تور مجازی + نوتیف بازدید + PWA موبایل):**

```bash
cd /var/www/posheh
./scripts/deploy.sh cursor/release-deploy-e117
```

⚠️ **نام شاخه با خط تیره `-` است، نه `/`:**
- ❌ `cursor/visit/notifications-e117`
- ✅ `cursor/visit-notifications-e117` یا ✅ `cursor/release-deploy-e117`

شاخه `main` احتمالاً عقب‌تر از آخرین کارهاست. PRهای Draft مهم: **#51** (تور مجازی)، **#52** (نوتیف بازدید)، **#53** (release deploy)، **#54** (PWA + بیلد اندروید/ویندوز).

مستندات deploy: `docs/DEPLOY.md` | نوتیف بازدید: `docs/VISIT-NOTIFICATIONS.md` | تور مجازی: `docs/VIRTUAL-TOUR.md`

---

## ۲. محصول و مخاطب

### مخاطب
- مشاوران مستقل املاک
- دفاتر املاک کوچک تا متوسط (۱ تا ۱۰ مشاور)
- مدیران دفتر که به فایلینگ، CRM، حسابداری، وبسایت و بازاریابی نیاز دارند

### ارزش پیشنهادی
- فایلینگ حرفه‌ای ملک با کد یکتا
- CRM مشتری و مالک
- اشتراک فایل در واتساپ، تلگرام، روبیکا، بله
- وبسایت اختصاصی هر دفتر
- اپ اندروید / PWA / دسکتاپ ویندوز
- اشتراک ماهانه با دوره آزمایشی

### پلن‌های فروش (فعلی در seeder)

| slug | نام | قیمت ماهانه | کاربر | ملک |
|------|-----|-------------|-------|-----|
| `solo` | مشاور مستقل | ۵۹۰,۰۰۰ تومان | ۱ | ۱۵۰ |
| `office` | دفتر حرفه‌ای | ۹۹۰,۰۰۰ تومان | ۵ | ۷۵۰ |
| `premium` | دفتر پریمیوم | ۱,۶۹۰,۰۰۰ تومان | ۱۰ | ۱۵۰۰ |

---

## ۳. معماری و استک فنی

### لایه‌ها (Clean Architecture)

```
Presentation  → Controllers, Resources, Middleware
Application   → Services, DTOs, Jobs, Events
Domain        → Models, Enums, Policies
Infrastructure → Repositories, DB, Cache, Queue
```

جزئیات: `docs/architecture/README.md`

### استک

| لایه | تکنولوژی |
|------|----------|
| Backend | Laravel 12, PHP 8.4, MySQL 8.4, Redis 7 |
| Frontend وب | React 19, TypeScript, TailwindCSS 4 |
| موبایل | Flutter (Android + iOS PWA) |
| دسکتاپ | Flutter Desktop (Windows) |
| زیرساخت | Docker, Nginx, Docker Compose |

### Multi-Tenancy
- هر دفتر (`offices`) مستقل است
- فیلد `office_id` روی جداول tenant-scoped
- Trait `BelongsToOffice` فیلتر خودکار
- Super Admin از scope خارج می‌شود

### احراز هویت
```
موبایل → OTP → SMS (IPPanel/MaxSMS) → Verify → Sanctum Token
```

### جریان اشتراک
```
انتخاب پلن → درگاه (زیبال / کیف پول / کافه‌بازار) → پرداخت → فعال‌سازی
```

---

## ۴. ساختار ریپو

```
Posheh/
├── backend/              # Laravel API
│   ├── app/
│   │   ├── Services/     # منطق اصلی کسب‌وکار
│   │   ├── Http/Controllers/
│   │   ├── Models/
│   │   └── Enums/
│   ├── database/migrations/
│   ├── database/seeders/
│   └── routes/api.php
├── frontend/             # React SPA (لندینگ + پنل دفتر + پنل ادمین)
│   ├── src/pages/
│   ├── src/components/
│   └── dist/             # خروجی build (روی سرور)
├── mobile/               # Flutter Android/iOS
├── desktop/              # Flutter Desktop
├── docker/               # nginx, mail, ...
├── scripts/              # deploy, SMS, mail, blog seed, ...
├── docs/                 # مستندات فنی
├── SEO-POSHE/            # پایگاه دانش SEO و محتوا (۳۰+ فایل)
├── docker-compose.yml
└── README.md
```

---

## ۵. دامنه‌ها و زیرساخت

| آدرس | کاربرد |
|------|--------|
| `posheapp.ir` | لندینگ + پنل دفاتر (SPA) |
| `www.posheapp.ir` | همان |
| `panel.posheapp.ir` | پنل مدیریت پلتفرم (Super Admin) |
| `{slug}.posheapp.ir` | وبسایت عمومی هر دفتر |
| `mail.posheapp.ir` | Mailu (ایمیل Info@posheapp.ir) |
| `/api/v1/*` | REST API |
| `/sitemap.xml` | نقشه سایت اصلی |
| `/sitemap-blog.xml` | نقشه مقالات بلاگ (GSC) |
| `/downloads/posheh-android.apk` | APK اندروید |

Nginx: `docker/nginx/default.conf` — wildcard `*.posheapp.ir` از قبل پشتیبانی می‌شود.

---

## ۶. سرور production

### مشخصات
- **مسیر پروژه:** `/var/www/posheh`
- **همان سرور قبلی** — IP و SSH از مالک قبلی بگیر
- **Deploy script:** `./scripts/deploy.sh [branch]`

### سرویس‌های Docker
```bash
docker compose ps
# معمولاً: app, nginx, mysql, redis, queue, scheduler
```

### دستورات پرکاربرد روی سرور
```bash
cd /var/www/posheh

# استقرار کامل
./scripts/deploy.sh main

# یا از شاخه PR جدید (قبل از merge)
./scripts/deploy.sh cursor/plan-pricing-ai-notifications-e117

# مایگریشن
docker compose exec app php artisan migrate --force

# ساخت sitemap
docker compose exec app php artisan sitemap:generate

# لاگ
docker compose logs app --tail=100
docker compose exec app tail -30 storage/logs/otp-sms.log

# SMS
docker compose exec app php artisan system:sms-enable
./scripts/diagnose-otp.sh
```

### بررسی سلامت بعد از deploy
```bash
curl -I https://posheapp.ir/sitemap-blog.xml   # باید Content-Type: application/xml
curl -s https://posheapp.ir/api/v1/plans | head
curl -I https://posheapp.ir/demo/sphere.jpg    # تور مجازی دمو
```

---

## ۷. وضعیت Git و Pull Requestها

### شاخه اصلی
- **`main`**: شاخه پایه production (ممکن است قدیمی باشد)
- **الگوی شاخه‌های agent:** `cursor/<نام-توصیفی>-e117`

### PRهای مهم (آخرین کارها — هنوز Draft)

| PR | شاخه | موضوع | وضعیت |
|----|------|-------|--------|
| #46 | `cursor/plan-pricing-ai-notifications-e117` | تعرفه حرفه‌ای، نوتیفیکیشن، AI محتوا، کیف پول، باگ‌فیکس‌ها | **مهم‌ترین — جامع‌ترین** |
| #45 | `cursor/admin-analytics-tours-themes-e117` | آمار ادمین، تم وبسایت، تور مجازی، sitemap | Draft |
| #44 | `cursor/sitemap-team-blog-images-e117` | sitemap GSC، SEO تصاویر بلاگ، edit/delete مشاور | Draft |
| #43 | `cursor/release-ready-downloads-seo-e117` | بلاگ slug، PWA، دانلود، اشتراک Rubika/Bale | Draft |

### PRهای قدیمی‌تر (مرجع)
- #40 تور مجازی ۳۶۰ کامل
- #38 OTP از سرور هلند (JSPD)
- #32 SEO بلاگ و sitemap
- #24 زیبال، backup، sitemap
- #23 موبایل/ویندوز parity با وب

### توصیه deploy
```bash
# گزینه ۱: merge PR #46 به main و deploy
git checkout main
git merge origin/cursor/plan-pricing-ai-notifications-e117
git push origin main
./scripts/deploy.sh main

# گزینه ۲: deploy مستقیم از شاخه PR (موقت)
./scripts/deploy.sh cursor/plan-pricing-ai-notifications-e117
```

**PR #46 شامل cherry-pick از #45 (تم و تیم) است** — برای deploy سریع، همین شاخه کافی است.

---

## ۸. تاریخچه کارها (از مکالمات Cursor)

این بخش خلاصه مکالمات طولانی با Cursor Agent است تا بدانی «چرا» و «چه چیزی» ساخته شده.

### فاز ۱ — زیرساخت و بازار
- راه‌اندازی Laravel + React + Docker
- OTP با IPPanel/MaxSMS (مشکلات سرور خارج ایران → relay و JSPD)
- پنل Super Admin در `panel.posheapp.ir`
- اشتراک، زیبال، کیف پول، کافه‌بازار
- فایلینگ، CRM، تیم، قراردادها

### فاز ۲ — SEO و محتوا
- بلاگ با ۳۰۰+ مقاله SEO (`blog:seed --count=300`)
- پوشه `SEO-POSHE/` با keyword database، templates، GSC guide
- sitemap داینامیک (`sitemap:generate`)
- خطای GSC: `sitemap-blog.xml` با `content-type: text/html` → **رفع در nginx**

### فاز ۳ — تور مجازی ۳۶۰
- آپلود پانوراما، ویرایشگر، لینک عمومی `/tour/{slug}`
- فایل دمو: `frontend/dist/demo/sphere.jpg`
- مستندات: `docs/VIRTUAL-TOUR.md`

### فاز ۴ — تم وبسایت دفاتر
- حداقل ۳ تم در `backend/config/office-themes.php`
- UI انتخاب تم در `OfficeWebsitePage.tsx`
- API: `OfficeSiteService::updateTheme()`

### فاز ۵ — آمار و تحلیل ادمین
- `PlatformAnalyticsController`, `PlatformUsersReportService`
- گزارش کاربران فعال/غیرفعال به تفکیک Windows/Android/PWA
- خروجی Excel

### فاز ۶ — تعرفه، نوتیفیکیشن، AI (آخرین کارها — PR #46)
- جدول مقایسه حرفه‌ای پلن‌ها (`PlanComparisonSection`, `planFeatures.ts`)
- نوتیفیکیشن درون‌پنلی (`NotificationBell`, `UserNotificationService`)
- **دستیار AI محتوا** (`ContentAssistantService`) — ۱۷+ نوع خروجی (ریلز، استوری، کپشن، ...)
- AI فعلاً **rule-based** (بدون OpenAI) — Premium gate در صفحه
- کیف پول در داشبورد + شارژ از زیبال
- رفع باگ پرداخت کیف پول (trial باقی می‌ماند)

### فاز ۷ — مرکز ارتباطات + تور مجازی enterprise (PR #55 — `cursor/customer-communication-e117`)
- ماژول Communication (چت وب، تلگرام، اینباکس ادمین، لید)
- ویجت چت شناور روی **صفحات عمومی** (`communicationWidgetVisibility.ts`)
- ادغام Smart Walk + ویرایشگر تور ۳۶۰ enterprise در پنل
- رفع زوم پانوراما، موزیک، اندروید، انتشار مجدد، واتساپ/بازدید تور
- **جزئیات کامل:** `docs/CURSOR-AGENT-CHANGELOG-FA.md`

### باگ‌های گزارش‌شده توسط مالک
1. پرداخت با کیف پول → اشتراک فعال نمی‌شد، هنوز «۳ روز آزمایشی» → **رفع در PR #46**
2. کیف پول در داشبورد نبود → **اضافه شد**
3. حذف/ویرایش مشاور نبود → **در TeamPage + OfficeService**
4. تم وبسایت قابل انتخاب نبود → **در OfficeWebsitePage**
5. ماژول AI در منو نبود → **همیشه در سایدبار، gate در صفحه**

---

## ۹. قابلیت‌های پیاده‌سازی‌شده

### پنل دفتر (Tenant)
- [x] فایلینگ ملک (۹ نوع، media، QR، share)
- [x] CRM مشتری و مالک
- [x] تقویم بازدید
- [x] مدیریت تیم (افزودن، ویرایش، حذف مشاور)
- [x] اشتراک و تمدید (زیبال، کیف پول، کافه‌بازار)
- [x] کیف پول + شارژ
- [x] وبسایت دفتر + تم‌ها
- [x] تور مجازی ۳۶۰
- [x] ربات تلگرام/واتساپ (webhook)
- [x] قراردادها و قالب‌ها
- [x] نوتیفیکیشن درون‌پنلی
- [x] دستیار AI محتوا (Premium)
- [x] حسابداری، کمیسیون (پلن office+)
- [x] چت تیمی

### پنل پلتفرم (`panel.posheapp.ir`)
- [x] مدیریت دفاتر، کاربران، اشتراک
- [x] کیف پول و پرداخت‌ها
- [x] تیکت پشتیبانی
- [x] تنظیمات سیستم (SMS، trial، ...)
- [x] آمار و analytics (در PR #45)
- [x] Impersonate کاربر
- [x] مدیریت بلاگ

### لندینگ و SEO
- [x] صفحات فارسی RTL
- [x] بلاگ + دسته‌بندی
- [x] sitemap.xml + sitemap-blog.xml
- [x] مقایسه پلن‌ها در لندینگ
- [x] دانلود APK

### موبایل
- [x] Flutter Android (کافه‌بازار)
- [x] PWA از frontend
- [x] Windows desktop build

---

## ۱۰. پلن‌ها و تعرفه

### کاتالوگ امکانات
فایل مرجع: `frontend/src/constants/planFeatures.ts`

### امکانات Premium (کلیدی)
- `content_assistant` — دستیار AI
- `website_listing` — وبسایت اختصاصی
- `verified_badge` — تیک تأیید
- `whatsapp_bot` — ربات واتساپ
- `advanced_analytics` — تحلیل پیشرفته

### Gate در UI
- `planHasFeature(planFeatures, 'content_assistant')` در صفحه AI
- سایدبار: AI همیشه visible (تغییر اخیر — gate فقط در صفحه)

### Seeder پلن‌ها
`backend/database/seeders/DatabaseSeeder.php` → متد `seedPlans()`

---

## ۱۱. باگ‌های گزارش‌شده و وضعیت رفع

| باگ | علت | رفع | PR |
|-----|-----|-----|-----|
| کیف پول پرداخت می‌کند ولی trial باقی می‌ماند | `trial_ends_at` در `activateSubscription` پاک نمی‌شد | `trial_ends_at => null` | #46 |
| UI بعد از پرداخت refresh نمی‌شد | فرانت `refreshUser()` نداشت | اضافه شد | #46 |
| sitemap-blog.xml در GSC خطا | nginx به SPA می‌رفت → HTML | route nginx + `application/xml` | #45/#46 |
| AI در منو نیست | شرط Premium در sidebar | همیشه نمایش، gate در صفحه | #46 |
| تم انتخاب نمی‌شود | کد ناقص/merge نشده | OfficeWebsitePage + themes config | #45/#46 |
| حذف مشاور نیست | API/UI نبود | TeamPage + OfficeService | #44/#46 |
| تور مجازی گیر ۰٪ loading | URL localhost + تداخل PSV | `resolvePanoramaUrl` + VT plugin | #51 |
| نوتیف بازدید وبسایت نیست | merge نشده بود | NotificationBell + VisitRequestNotifier | #52 |
| PWA موبایل سایز بد | safe-area و padding | AppLayout + index.css | #54 |
| deploy نام شاخه اشتباه | `/` به‌جای `-` در نام branch | `deploy.sh` + `docs/DEPLOY.md` | #53 |
| Smart Walk زوم/پینچ | wheel passive + ref timing + overlay | native wheel + multi-pointer pinch | #54 |
| badge نوتیف همیشه ۱ | announcements همیشه unread می‌شد | فقط unread DB در شمارنده | #54 |
| انتشار تور fail | snapshot/log خطا می‌داد | publish resilient + UI errors | #54 |

---

## ۱۲. متغیرهای محیطی مهم

فایل: `backend/.env` (از `backend/.env.example`)

```env
# عمومی
APP_URL=https://posheapp.ir
APP_ENV=production
APP_DEBUG=false

# دیتابیس
DB_HOST=mysql
DB_DATABASE=posheh
DB_USERNAME=posheh
DB_PASSWORD=<از مالک قبلی>

# Redis
REDIS_HOST=redis
CACHE_STORE=file          # deploy.sh به file تنظیم می‌کند
QUEUE_CONNECTION=redis

# پرداخت
ZIBAL_MERCHANT=<merchant-id>
ZIBAL_SANDBOX=false

# کافه‌بازار
CAFE_BAZAAR_API_TOKEN=<JWT از پنل کافه‌بازار>
CAFE_BAZAAR_PACKAGE_NAME=ir.posheapp.posheh

# SMS (IPPanel / MaxSMS)
SMS_PROVIDER=maxsms
SMS_MODE=live             # log = OTP ثابت 123456
IPPANEL_API_MODE=jspd     # یا edge
IPPANEL_USERNAME=
IPPANEL_PASSWORD=
IPPANEL_OTP_PATTERN_CODE=qhhly1nai3njev0

# SMS relay (اگر سرور خارج ایران)
SMS_RELAY_URL=
SMS_RELAY_SECRET=

# تلگرام
TELEGRAM_WEBHOOK_BASE_URL=https://posheapp.ir

# ایمیل (Mailu)
MAIL_MAILER=smtp
MAIL_HOST=mailu-front
MAIL_USERNAME=Info@posheapp.ir
```

**هشدار:** مقادیر واقعی `.env` روی سرور است و در Git نیست. از مالک قبلی بگیر.

---

## ۱۳. استقرار (Deploy)

### روش استاندارد (همه فیچرها یکجا)
```bash
cd /var/www/posheh
./scripts/deploy.sh cursor/release-deploy-e117
```

### روش پایدار (فقط main بعد از merge)
```bash
cd /var/www/posheh
./scripts/deploy.sh main
```

`scripts/deploy.sh` این کارها را انجام می‌دهد:
1. fetch + checkout شاخه
2. docker compose up
3. migrate
4. seed (settings, blog, sitemap, ...)
5. build frontend (`npm ci && npm run build`)
6. restart nginx, app, queue
7. health check

### Deploy دستی (خلاصه)
```bash
git fetch origin
git checkout main && git pull
docker compose up -d --build
docker compose exec app php artisan migrate --force
docker compose exec app php artisan sitemap:generate
cd frontend && npm ci && npm run build
docker compose restart nginx app queue
```

### بعد از deploy حتماً تست کن
- [ ] ورود OTP
- [ ] خرید اشتراک با زیبال
- [ ] پرداخت با کیف پول (trial پاک شود)
- [ ] شارژ کیف پول
- [ ] انتخاب تم وبسایت
- [ ] حذف/ویرایش مشاور
- [ ] AI در منو + gate Premium
- [ ] `curl -I .../sitemap-blog.xml` → XML
- [ ] `panel.posheapp.ir` → پنل ادمین (نه لندینگ)

---

## ۱۴. حساب‌های دمو

| نقش | موبایل | OTP (حالت log) | رمز (اگر فعال) |
|-----|--------|----------------|----------------|
| Super Admin | 09120000000 | 123456 | Posheh@2026 |
| مدیر دفتر نمونه | 09121111111 | 123456 | Posheh@2026 |
| مشاور | 09122222222 | 123456 | Posheh@2026 |

اگر `SMS_MODE=log` باشد، OTP همیشه `123456` است.

---

## ۱۵. نقشه فایل‌های کلیدی

### Backend — Services
| فایل | کاربرد |
|------|--------|
| `app/Services/Subscription/SubscriptionService.php` | اشتراک، کیف پول، زیبال |
| `app/Services/Wallet/WalletService.php` | شارژ و موجودی کیف پول |
| `app/Services/Ai/ContentAssistantService.php` | دستیار AI محتوا |
| `app/Services/Ai/OfficeContextBuilder.php` | context دفتر برای AI |
| `app/Services/Office/OfficeService.php` | تیم: update/remove member |
| `app/Services/Office/OfficeSiteService.php` | تم وبسایت دفتر |
| `app/Services/Notification/UserNotificationService.php` | نوتیفیکیشن |
| `app/Services/Admin/PlatformUsersReportService.php` | گزارش کاربران |

### Backend — Controllers
| فایل | کاربرد |
|------|--------|
| `app/Http/Controllers/Api/WalletController.php` | API کیف پول |
| `app/Http/Controllers/Api/Ai/ContentAssistantController.php` | API AI |
| `app/Http/Controllers/SitemapController.php` | sitemap |
| `app/Http/Controllers/Api/Admin/PlatformAnalyticsController.php` | آمار ادمین |

### Frontend — Pages
| فایل | کاربرد |
|------|--------|
| `src/pages/SubscriptionPage.tsx` | اشتراک + مقایسه پلن |
| `src/pages/DashboardPage.tsx` | داشبورد + WalletCard |
| `src/pages/TeamPage.tsx` | مدیریت تیم |
| `src/pages/OfficeWebsitePage.tsx` | تم وبسایت |
| `src/pages/ContentAssistantPage.tsx` | دستیار AI |
| `src/pages/VirtualTourEditorPage.tsx` | تور ۳۶۰ |

### Frontend — Components
| فایل | کاربرد |
|------|--------|
| `src/components/wallet/WalletCard.tsx` | کارت کیف پول |
| `src/components/plans/PlanComparisonSection.tsx` | جدول مقایسه |
| `src/components/notifications/NotificationBell.tsx` | زنگ نوتیفیکیشن |
| `src/constants/planFeatures.ts` | کاتالوگ امکانات |

### Config / Infra
| فایل | کاربرد |
|------|--------|
| `backend/config/office-themes.php` | تم‌های وبسایت |
| `docker/nginx/default.conf` | routing + sitemap XML |
| `scripts/deploy.sh` | استقرار خودکار |
| `backend/routes/api.php` | همه routeهای API |

---

## ۱۶. مستندات جانبی

| فایل | موضوع |
|------|--------|
| `README.md` | شروع سریع |
| `docs/INSTALLATION.md` | نصب production |
| `docs/PANEL.md` | پنل ادمین |
| `docs/SUPER-ADMIN-PROMPT.md` | ۵۲+ قابلیت ادمین |
| `docs/VIRTUAL-TOUR.md` | تور ۳۶۰ |
| `docs/EMAIL-SETUP.md` | Mailu |
| `docs/SMS-EDGE-ABROAD.md` | SMS از سرور خارج |
| `docs/SMS-RELAY.md` | relay ایران |
| `docs/CAFE-BAZAAR.md` | IAP اندروید |
| `SEO-POSHE/README.md` | پایگاه SEO |
| `SEO-POSHE/21-Cursor-Rules.md` | قوانین تولید محتوا |

---

## ۱۷. کارهای باز و اولویت‌ها

### فوری (قبل از تحویل به مشتری نهایی)
1. **Merge PR #46** (و در صورت نیاز #43–#45) به `main`
2. **Deploy واقعی** روی `posheapp.ir` از `main`
3. **تست end-to-end** همه باگ‌های گزارش‌شده
4. **GSC:** Submit مجدد `sitemap-blog.xml` بعد از deploy

### مهم (کوتاه‌مدت)
5. Enforce پلن در backend برای همه endpointها (نه فقط UI)
6. اتصال OpenAI واقعی به `ContentAssistantService` (اختیاری — فعلاً rule-based)
7. History UI برای خروجی‌های AI
8. بستن PRهای قدیمی تکراری (#5, #6, ...)

### متوسط‌مدت
9. اپ موبایل parity کامل با وب
10. ربات واتساپ production-ready
11. گزارش MRR/churn در پنل ادمین
12. backup خودکار و monitoring

### اختیاری
13. iOS App Store
14. White-label برای دفاتر بزرگ
15. API عمومی برای integrations

---

## ۱۸. دستورالعمل برای Cursor Agent

وقتی کاربر می‌گوید «فایل .md را بخوان»، این مراحل را انجام بده:

### ۱. خواندن زمینه
```
Read: docs/CURSOR-HANDOFF-FA.md (این فایل)
Read: README.md
Check: gh pr list — وضعیت PRهای باز
Check: git branch -a — شاخه فعال
```

### ۲. قبل از هر تغییر
- بپرس یا بررسی کن: deploy از `main` است یا شاخه PR؟
- شاخه جدید بساز: `cursor/<نام>-e117`
- از conventions موجود پیروی کن (فارسی RTL، Services در backend)

### ۳. بعد از تغییر
```bash
git add ...
git commit -m "..."
git push -u origin cursor/<branch>-e117
# سپس PR با ManagePullRequest
```

### ۴. Deploy روی سرور
```bash
ssh <server>
cd /var/www/posheh
./scripts/deploy.sh <branch>
```

### ۵. قوانین کدنویسی این پروژه
- تغییرات کم و focused
- بدون over-engineering
- کامنت فقط برای منطق غیرواضح
- تست فقط اگر معنادار باشد
- UI فارسی RTL
- قیمت‌ها به تومان

### ۶. SMS / OTP
اگر OTP کار نکرد:
```bash
./scripts/diagnose-otp.sh
docker compose exec app php artisan system:sms-probe 09XXXXXXXXX --send
```
مستندات: `docs/SMS-EDGE-ABROAD.md`

### ۷. SEO / بلاگ
برای مقاله جدید: `SEO-POSHE/` را بخوان، مخصوصاً `21-Cursor-Rules.md`

---

## ۱۹. چک‌لیست تحویل

### از مالک قبلی بگیر
- [ ] دسترسی SSH سرور
- [ ] `backend/.env` کامل
- [ ] `docker/mail/secrets.env`
- [ ] Merchant ID زیبال
- [ ] JWT کافه‌بازار
- [ ] credentialهای IPPanel/SMS
- [ ] دسترسی GitHub repo (admin)
- [ ] Google Search Console
- [ ] Google Analytics (اگر دارد)
- [ ] پنل کافه‌بازار
- [ ] دامنه `posheapp.ir` (DNS)

### بعد از تحویل تأیید کن
- [ ] `git clone` و `docker compose up` لوکال کار می‌کند
- [ ] production با آخرین کد sync است
- [ ] OTP زنده می‌رود (یا log mode برای تست)
- [ ] پرداخت زیبال تست شده
- [ ] sitemap در GSC سبز است
- [ ] APK دانلود می‌شود
- [ ] panel.posheapp.ir جدا از لندینگ است

---

## پیوست: دستور شروع سریع برای Cursor

کپی کن در چت Cursor:

```
پروژه پوشه (Posheh) — SaaS املاک ایرانی.
ریپو: github.com/Siahgoosh/Posheh
سرور: /var/www/posheh — دامنه posheapp.ir

لطفاً این فایل‌ها را بخوان:
1. docs/CURSOR-HANDOFF-FA.md (اولویت اول — کل داستان)
2. docs/CURSOR-AGENT-CHANGELOG-FA.md (آخرین تغییرات Agent — PR #55)
3. README.md
4. وضعیت PR #55 (customer-communication-e117)

main احتمالاً عقب است؛ آخرین کار روی cursor/customer-communication-e117 (PR #55).

از همین‌جا ادامه بده. قبل از deploy بپرس.
```

---

## تماس و مالکیت

- **ریپو GitHub:** Siahgoosh/Posheh
- **لایسنس:** Proprietary — تمام حقوق محفوظ
- **ایمیل سیستم:** Info@posheapp.ir

---

*این سند توسط Cursor Agent برای انتقال پروژه تهیه شده است. در صورت تغییرات مهم، این فایل را به‌روز کن.*
