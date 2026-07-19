# انتشار اپ پوشه — نسخه ۱.۱.۰

## فایل‌های آماده دانلود

| پلتفرم | لینک |
|--------|------|
| **اندروید APK** | https://posheapp.ir/downloads/posheh-android.apk |
| **ویندوز ZIP** | https://posheapp.ir/downloads/posheh-windows.zip |
| **صفحه دانلود** | https://posheapp.ir/download |

نسخه: **1.1.0+5** — هم‌تراز کامل با وب

## قابلیت‌های نسخه ۱.۱.۰

- پرداخت و تمدید اشتراک درون‌اپ (زیبال)
- وبسایت اختصاصی دفتر
- ثبت ملک با فیلدهای داینامیک + آپلود تصویر
- اشتراک‌گذاری و کپی متن آگهی
- جستجوی پیشرفته با فیلتر
- گزارش KPI کامل
- تنظیمات ربات/برند و API Key (مدیر)
- اعلان‌های درون‌اپ با ناوبری مستقیم

## متن آگهی دیوار

**عنوان:** اپ مدیریت املاک پوشه — اندروید و ویندوز | ۳ روز رایگان

**متن:**

سامانه **پوشه** — مدیریت حرفه‌ای املاک برای مشاور و دفتر:

✅ ثبت ملک با فیلدهای تخصصی و عکس  
✅ مالک، مشتری، بازدید شمسی، CRM  
✅ حسابداری، کمیسیون، قرارداد PDF  
✅ دعوت تیم، تیکت پشتیبانی  
✅ پرداخت و تمدید اشتراک داخل اپ  
✅ وبسایت اختصاصی دفتر (پلن حرفه‌ای)  
✅ اندروید + ویندوز همگام با نسخه وب  

📥 **اندروید:** https://posheapp.ir/downloads/posheh-android.apk  
💻 **ویندوز:** https://posheapp.ir/downloads/posheh-windows.zip  
🌐 **ثبت‌نام:** https://posheapp.ir/register  

پلن فردی: **۳ روز رایگان**

---

## Build برای انتشار

```bash
# GitHub Actions
Actions → Build Android & Windows → Run workflow

# یا محلی
cd mobile
flutter build apk --release --dart-define=API_URL=https://posheapp.ir/api/v1
flutter build windows --release --dart-define=API_URL=https://posheapp.ir/api/v1
```

## Deploy سرور

```bash
cd /var/www/posheh && ./scripts/deploy.sh cursor/mobile-web-parity-divar-e117
docker compose exec app php artisan migrate --force
```

## نصب

**اندروید:** دانلود APK → اجازه نصب از منبع ناشناس → نصب  
**ویندوز:** Extract ZIP → اجرای `posheh.exe`
