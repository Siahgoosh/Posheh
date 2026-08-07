# تور مجازی ۳۶۰ درجه — پوشه

پیاده‌سازی حرفه‌ای مشابه [360nama.com](https://360nama.com/realstates-virtual-tour/)

## امکانات

| قابلیت | وضعیت |
|--------|--------|
| بازدید ۳۶۰ درجه تعاملی | ✅ |
| حرکت بین صحنه‌ها (هات‌اسپات) | ✅ |
| نقاط اطلاعاتی (info hotspot) | ✅ |
| پلان موقعیت (floor plan radar) | ✅ |
| گالری تصاویر | ✅ |
| فرم درخواست بازدید / مشاوره | ✅ |
| واتساپ و تماس | ✅ |
| نقشه گوگل | ✅ |
| اشتراک‌گذاری لینک | ✅ |
| آمار بازدید و سرنخ | ✅ |
| ژیروسکوپ موبایل | ✅ |
| VR mode (Photo Sphere Viewer) | ✅ |
| برندینگ دفتر (رنگ، لوگو) | ✅ |
| آپلود پانورامای واقعی | ✅ |

## مسیرها

- **عمومی:** `https://posheapp.ir/tour/{slug}` — برای مشتریان
- **مدیریت:** `/virtual-tours` — لیست تورها
- **ویرایش:** `/virtual-tours/{id}/edit` — افزودن صحنه و هات‌اسپات

## API

| Method | Path | توضیح |
|--------|------|------|
| GET | `/api/v1/tour/{slug}` | مشاهده عمومی (ثبت بازدید) |
| POST | `/api/v1/tour/{slug}/lead` | فرم درخواست بازدید |
| GET | `/api/v1/virtual-tours` | لیست تورهای دفتر |
| POST | `/api/v1/virtual-tours` | ایجاد تور |
| POST | `/api/v1/virtual-tours/{id}/scenes` | افزودن صحنه (+ آپلود panorama) |
| PUT | `/api/v1/virtual-tours/{id}/scenes/{sceneId}/hotspots` | هات‌اسپات‌ها |

## نمونه دمو

پس از `php artisan db:seed`:

```
/tour/demo-apartment-pasdaran
```

## فاز ۱ و ۲ — ۳۰ فیچر

### فاز ۱ (نرم‌افزار اصلی)
1. تور مجازی ۳۶۰ درجه ✅
2. صفحه جزئیات ملک ✅
3. CRM قیف فروش (کانبان) ✅
4. امتیازدهی سرنخ (Lead Score) ✅
5. تسهیم خودکار کمیسیون ✅
6. حسابداری — خلاصه مالی ✅
7. علاقه‌مندی‌ها ✅
8. مدیریت تیم ✅
9. اشتراک ✅
10. تنظیمات ✅
11. کپی آگهی چندپلتفرمی ✅
12. تقویم تمدید اجاره ✅
13. تاریخچه قیمت ملک ✅
14. مقایسه فایل‌ها (API) ✅
15. فرم سرنخ در تور مجازی ✅

### فاز ۲ (پنل ادمین + تکمیلی)
16. Health Score دفاتر ✅
17. تعلیق/فعال‌سازی دفتر ✅
18. MRR/ARR گزارش ✅
19. مدیریت کوپن ✅
20. Feature Flag ✅
21. Audit Logs ✅
22. آمار تور مجازی پلتفرم ✅
23. پنل ادمین UI ✅
24. یادآور پیگیری CRM (next_follow_up) ✅
25. گزارش KPI کمیسیون ماهانه ✅
26. آپلود مدیا تور ✅
27. انتشار/پیش‌نویس تور ✅
28. QR و لینک اشتراک ✅
29. نقشه در تور ✅
30. ژیروسکوپ/VR موبایل ✅

## فاز ۳ — Enterprise

### پنل مدیریت (`/virtual-tours`)
- داشبورد آماری (کل، منتشر شده، پیش‌نویس، بایگانی، بازدید، سرنخ)
- Draft / Publish / Archive / Duplicate
- Import JSON · Export JSON · Export ZIP
- Backup · Version History · Restore
- Activity Log

### اشتراک‌گذاری (تب «اشتراک» در ویرایشگر)
- لینک عمومی و خصوصی (توکن)
- QR Code · Embed / Iframe (`/embed/tour/{slug}`)
- Public / Private · رمز دسترسی · تاریخ انقضا

### امنیت
- اعتبارسنجی MIME و حجم پانوراما (`config/virtual-tour.php`)
- CSRF/Sanctum برای API احراز هویت‌شده
- XSS-safe serialization · SQL injection via Eloquent
- دسترسی office-scoped · لاگ فعالیت

### Performance
- Lazy loading صحنه‌ها (VirtualTourPlugin client mode)
- Throttle به‌روزرسانی position (requestAnimationFrame)
- CDN-ready URLs (`VT_CDN_URL`)
- فشرده‌سازی تصویر سمت کلاینت (`utils/imageCompression.ts`)

### API Enterprise

| Method | Path | توضیح |
|--------|------|------|
| GET | `/api/v1/virtual-tours/dashboard` | آمار داشبورد |
| POST | `/api/v1/virtual-tours/import` | Import JSON |
| POST | `/api/v1/virtual-tours/{id}/duplicate` | کپی تور |
| POST | `/api/v1/virtual-tours/{id}/publish` | انتشار |
| POST | `/api/v1/virtual-tours/{id}/archive` | بایگانی |
| PUT | `/api/v1/virtual-tours/{id}/sharing` | تنظیمات اشتراک |
| GET | `/api/v1/virtual-tours/{id}/export/json` | Export JSON |
| GET | `/api/v1/virtual-tours/{id}/export/zip` | Export ZIP |
| POST | `/api/v1/virtual-tours/{id}/backup` | پشتیبان |
| GET | `/api/v1/virtual-tours/{id}/versions` | تاریخچه نسخه |
| POST | `/api/v1/virtual-tours/{id}/versions/{vid}/restore` | بازیابی |
| POST | `/api/v1/tour/{slug}/verify-password` | تأیید رمز عمومی |

## Deploy

روی سرور پوشه PHP روی host نصب نیست — از Docker استفاده کنید:

```bash
cd /var/www/posheh
./scripts/deploy.sh cursor/virtual-tour-enterprise-e117
```

فقط migration:

```bash
./scripts/migrate.sh
# یا: docker compose exec app php artisan migrate --force
```

### اتصال صحنه (Interactive Scene Linking)
1. تب **اتصال** → روی تصویر کلیک کنید
2. صحنه مقصد را انتخاب کنید
3. **ذخیره اتصال‌ها** — مختصات خودکار ثبت می‌شود

### متغیرهای محیطی (اختیاری)

```
VT_MAX_PANORAMA_MB=100
VT_CDN_URL=https://cdn.example.com
VT_VERSION_RETENTION=20
```

## آپلود پانوراما

فایل equirectangular (نسبت ۲:۱) را در ویرایشگر تور آپلود کنید. فرمت‌های JPG/PNG/WebP تا ۱۰۰MB (قابل تنظیم).
