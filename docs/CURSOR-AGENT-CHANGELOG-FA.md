# یادداشت تغییرات Cursor Agent — شاخه `cursor/customer-communication-e117`

> **آخرین به‌روزرسانی:** ۲۰۲۶-۰۸-۰۷  
> **PR:** https://github.com/Siahgoosh/Posheh/pull/55  
> **Deploy روی سرور:** `./scripts/deploy.sh cursor/customer-communication-e117`

این فایل خلاصه **همه تغییرات مهم این دوره کار** است تا در چت‌های بعدی گم نشود.  
سند کلی پروژه: `docs/CURSOR-HANDOFF-FA.md`

---

## ۱. مرکز ارتباطات (Communication Center)

| موضوع | جزئیات |
|--------|--------|
| ماژول | `backend/app/Modules/Communication/` + `frontend/src/features/communication/` |
| ویجت چت | `CommunicationWidgetRoot` + `FloatingChatWidget` |
| **نمایش ویجت** | فقط صفحات عمومی مارکتینگ (`/`, `/blog`, `/contact`, `/tour/…`) — **نه** پنل دفتر (`/dashboard`…) و **نه** `panel.posheapp.ir` |
| منطق visibility | `frontend/src/features/communication/communicationWidgetVisibility.ts` |
| اینباکس ادمین | `panel.posheapp.ir` → ارتباطات (`AdminCommunicationInboxPage`) |
| بک‌اند ادمین | `authorizeComm` — مقایسه با enum `UserRole` (نه رشته `'super_admin'`) |
| تلگرام اپراتور | `OperatorAlertService` — هشدار چت وب به بات |
| نصب | `php artisan communication:install --force` |
| Webhook تلگرام | دکمه «ثبت webhook» در ادمین؛ GET روی URL برای ping |

### رفع‌های ورود پنل
- `ConsultantDirectoryController` import در `routes/api.php`
- `auth:ensure-platform-admin` — `info@posheapp.ir` / `Posheh@2026`
- خطای 502 و پیام‌های واضح‌تر در `LoginPage`

---

## ۲. تور مجازی ۳۶۰ + Smart Walk

| موضوع | جزئیات |
|--------|--------|
| ادغام | `smart-walk-module-e117` داخل همین شاخه |
| پنل | `/virtual-tours`, ویرایشگر enterprise, Smart Walk |
| مسیرهای کلیدی | `frontend/src/features/virtual-tour/` |

### رفع زوم / پانوراما
- **علت:** `panorama_width/height` از اندازه thumbnail ذخیره می‌شد → زوم extreme در PSV
- `SceneManager.php` — بدون fallback thumbnail
- `utils/sceneDimensions.ts` — اعتبارسنجی equirectangular
- `useTourEngine.ts` — FOV بازتر در ویرایشگر
- سرور: `php artisan virtual-tour:repair-panorama-meta`

### موزیک
- فایل‌ها: `frontend/public/audio/virtual-tour/*.mp3` (+ `scripts/fetch-tour-audio.sh`)
- پیش‌نمایش موزیک در ویرایشگر

### اندروید / Smart Walk
- `object-fit: contain`, fit-scale اولیه، CSS viewport

### انتشار مجدد
- دکمه **بروزرسانی / انتشار مجدد** برای تورهای `published` در `TourEditorLayout`

### واتساپ و درخواست بازدید (صفحه عمومی تور)
- `utils/tourContact.ts` — نرمال شماره و `wa.me`
- `utils/tourPublicAccess.ts` — رمز/توکن تور برای API لید
- لید → `virtual_tour_leads` + `office_visit_requests` (تقویم بازدید دفتر)
- تحلیل تور: لیست `recent_leads`

---

## ۳. دستور Deploy کامل (سرور)

```bash
cd /var/www/posheh
git pull origin cursor/customer-communication-e117
./scripts/deploy.sh cursor/customer-communication-e117
./scripts/migrate.sh
docker compose exec app php artisan virtual-tour:repair-panorama-meta
docker compose exec app php artisan communication:install --force
docker compose exec app php artisan auth:ensure-platform-admin
cd frontend && npm run build && docker compose restart nginx
```

**تأیید deploy:** `frontend/dist/version.json` → git نزدیک `e72451b`؛ bundle پنل ~۱.۵MB

---

## ۴. حساب‌های تست

| کاربرد | ورود |
|--------|------|
| پنل پلتفرم | `panel.posheapp.ir` — `info@posheapp.ir` یا `posheh` / `Posheh@2026` |

---

## ۵. قوانین برای Agent بعدی

1. قبل از deploy شاخه را اینجا و `CURSOR-HANDOFF-FA.md` به‌روز کن.
2. ویجت چت را **فقط** با `communicationWidgetVisibility.ts` مخفی کن — نه با `isAuthenticated` سراسری.
3. تنظیمات تور (واتساپ/موزیک) بعد از ذخیره → **انتشار مجدد** برای نسخه عمومی.
4. PR اصلی این دوره: **#55** (base: `main`).

---

## ۶. تاریخچه کامیت‌های مهم (این شاخه)

```
e72451b — واتساپ تور، ثبت بازدید، لید با رمز تور
339bf00 — زوم تور، موزیک، اندروید، انتشار مجدد
18241d0 — merge Smart Walk
b304822 — مخفی کردن چت در پنل (اصلاح شده: فقط مسیرهای عمومی)
b6882ef — اینباکس ادمین + تلگرام اپراتور
c43cc6f — پایه Communication Center
```

---

*هر بار تغییر معنی‌دار: یک بند کوتاه به این فایل اضافه کن.*
