# یادداشت تغییرات Cursor Agent — شاخه `cursor/safe-bugfixes-batch-a876`

> **آخرین به‌روزرسانی:** ۲۰۲۶-۰۸-۱۱  
> **PR:** https://github.com/Siahgoosh/Posheh/pull/56  
> **Deploy روی سرور:** `./scripts/deploy.sh cursor/safe-bugfixes-batch-a876`

این فایل خلاصه **همه تغییرات مهم این دوره کار** است تا در چت‌های بعدی گم نشود.  
سند کلی پروژه: `docs/CURSOR-HANDOFF-FA.md`

---

## ۱. مرکز ارتباطات (Communication Center) — فعال‌سازی روی این شاخه

ماژول چت آنلاین از `cursor/customer-communication-e117` به این شاخه منتقل و سیم‌کشی شد.

| موضوع | جزئیات |
|--------|--------|
| ماژول | `backend/app/Modules/Communication/` + `frontend/src/features/communication/` |
| ویجت چت | `CommunicationWidgetRoot` روی صفحات عمومی مارکتینگ |
| اینباکس ادمین | `panel.posheapp.ir` → «مرکز ارتباطات» (`/communication`) |
| نصب | `php artisan communication:install --force` (در `deploy.sh` هم هست) |
| رفع | badge خوانده‌نشده هنگام بسته بودن ویجت (polling جدا) |

### دستور بعد از deploy
```bash
./scripts/deploy.sh cursor/safe-bugfixes-batch-a876
docker compose exec app php artisan communication:install --force
docker compose exec app php artisan communication:diagnose
# اختیاری: توکن ربات تلگرام را در تنظیمات ادمین بگذارید و «ثبت Webhook» را بزنید
```

---

## ۲. تور مجازی ۳۶۰ + Smart Walk

ادیتور کامل از شاخه communication بازگردانی شد؛ وابستگی‌های photo-sphere روی `5.15.1` پین شدند.

---

## ۳. باگ‌فیکس‌های امنیتی / پنل / موبایل

در کامیت‌های قبلی همین PR: سخت‌گیری API عمومی، tenancy، کیف پول، نقش ادمین، filing_data، لینک‌های پنل، impersonation، موبایل.

**ورود با پسورد / OTP غیرفعال دست‌نخورده مانده است.**

---

*هر بار تغییر معنی‌دار: یک بند کوتاه به این فایل اضافه کن.*
