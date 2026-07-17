# انتشار اپ پوشه در دیوار

## فایل‌های آماده دانلود

پس از merge و اجرای workflow **Build Android & Windows**:

| پلتفرم | آدرس مستقیم |
|--------|-------------|
| اندروید (APK) | https://posheapp.ir/downloads/posheh-android.apk |
| ویندوز (ZIP) | https://posheapp.ir/downloads/posheh-windows.zip |
| صفحه دانلود | https://posheapp.ir/download |

نسخه فعلی: **1.0.3+4**

## متن پیشنهادی آگهی دیوار

**عنوان:** اپ مدیریت املاک پوشه — اندروید و ویندوز (۳ روز رایگان)

**متن:**

سامانه ابری **پوشه** برای مشاوران و دفاتر املاک:

- ثبت و مدیریت ملک، مالک، مشتری
- تقویم بازدید شمسی
- CRM فروش و حسابداری دفتر
- کمیسیون، قرارداد، گزارش
- دعوت عضو تیم و پشتیبانی تیکتی
- اعلان‌های داخل اپ
- ورود با OTP (بدون رمز عبور)

**دانلود اندروید:** https://posheapp.ir/downloads/posheh-android.apk  
**دانلود ویندوز:** https://posheapp.ir/downloads/posheh-windows.zip  
**ثبت‌نام / ورود:** https://posheapp.ir/register

پلن فردی: **۳ روز رایگان** — سپس تمدید از سایت.

پشتیبانی: posheapp.ir

---

## نکات نصب برای خریداران

### اندروید
1. APK را دانلود کنید.
2. در تنظیمات، نصب از منابع ناشناس را برای مرورگر فعال کنید.
3. فایل را باز کرده و نصب کنید.

### ویندوز
1. ZIP را دانلود و Extract کنید.
2. `posheh.exe` را اجرا کنید.
3. در صورت هشدار SmartScreen: More info → Run anyway

## CI

```bash
# روی main یا workflow_dispatch
GitHub Actions → Build Android & Windows
```

برای کافه‌بازار: secrets مربوط به `ANDROID_KEYSTORE_*` باید تنظیم شده باشند.
