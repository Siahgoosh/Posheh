# نوتیفیکیشن درخواست بازدید وبسایت

آخرین به‌روزرسانی: ۱۴۰۴/۰۵/۰۳

## خلاصه

وقتی بازدیدکننده از وبسایت اختصاصی دفتر (`name.posheapp.ir`) درخواست بازدید ثبت می‌کند:

1. **پنل** — اعلان در زنگوله (گوشه بالا) برای مدیر دفتر و مشاوران فعال
2. **صفحه بازدیدها** — درخواست در بخش «درخواست‌های بازدید از وبسایت» نمایش داده می‌شود
3. **تلگرام** — اگر ربات و شناسه چت مدیر تنظیم شده باشد، پیام به مدیر ارسال می‌شود

## مسیر API

| مسیر | توضیح |
|------|--------|
| `POST /api/v1/sites/{subdomain}/visit-request` | ثبت درخواست از وبسایت عمومی |
| `GET /api/v1/office/website/visit-requests` | لیست درخواست‌ها در پنل |
| `GET /api/v1/notifications` | صندوق اعلان‌های درون‌برنامه‌ای |
| `POST /api/v1/notifications/{id}/read` | علامت‌گذاری خوانده‌شده |
| `POST /api/v1/notifications/read-all` | همه خوانده‌شده |

## فایل‌های کلیدی

### Backend

- `app/Services/Office/OfficeSiteService.php` — ثبت درخواست + فراخوانی نوتیف
- `app/Services/Office/VisitRequestNotifier.php` — اعلان پنل + تلگرام
- `app/Services/Office/TelegramBotService.php` — `notifyVisitRequest()`
- `app/Services/Notification/UserNotificationService.php` — صندوق اعلان
- `app/Notifications/InAppNotification.php` — ذخیره در جدول `notifications`
- `app/Http/Controllers/Api/NotificationController.php`

### Frontend

- `src/components/notifications/NotificationBell.tsx` — زنگوله اعلان در پنل
- `src/components/layout/AppLayout.tsx` — قرارگیری زنگوله
- `src/pages/VisitsPage.tsx` — نمایش درخواست‌های وبسایت
- `src/pages/OfficeWebsitePage.tsx` — لیست درخواست‌ها (بدون وابستگی به وضعیت published)

## تنظیم تلگرام

در **تنظیمات دفتر → ربات تلگرام**:

1. توکن ربات از BotFather
2. شناسه چت مدیر (User ID)
3. دکمه «اتصال webhook»

پس از ثبت درخواست بازدید، پیامی شبیه زیر به چت مدیر می‌رود:

```
📅 درخواست بازدید جدید
دفتر: ...
👤 نام
📱 موبایل
🏠 ملک: کد ...
🗓 زمان پیشنهادی
```

## رفع باگ‌های قبلی

| مشکل | علت | راه‌حل |
|------|-----|--------|
| اعلان‌ها بعد از deploy نبود | شاخه نوتیفیکیشن merge نشده بود | بازگردانی `NotificationBell` + API |
| درخواست در پنل نبود | فقط در صفحه وبسایت و فقط وقتی `published` | نمایش در `/visits` + حذف شرط published |
| تلگرام نمی‌آمد | هیچ کدی notify نمی‌کرد | `VisitRequestNotifier` + `notifyVisitRequest` |
| صف queue | `ShouldQueue` روی اعلان | اعلان هم‌زمان (بدون صف) |

## Deploy

```bash
cd /var/www/posheh
./scripts/deploy.sh cursor/release-deploy-e117
```

(شاخه یکجا: تور مجازی + نوتیف بازدید. نام اشتباه `cursor/visit/notifications-e117` کار نمی‌کند.)

مایگریشن جدید لازم نیست — جدول `notifications` از قبل وجود دارد.

## تست دستی

1. وبسایت دفتر را باز کنید و درخواست بازدید ثبت کنید
2. در پنل مدیر: زنگوله باید اعلان جدید نشان دهد
3. صفحه `/visits` — بخش درخواست‌های وبسایت
4. اگر تلگرام تنظیم است — پیام در چت مدیر

## تور مجازی (جداگانه)

درخواست بازدید از **تور مجازی ۳۶۰** در جدول `virtual_tour_leads` ذخیره می‌شود و هنوز به نوتیفیکیشن تلگرام/پنل وصل نیست — در صورت نیاز در فاز بعدی اضافه می‌شود.
