# رفع خطای Blocked by Play Protect — پوشه / کافه‌بازار

راهنمای رسمی کافه‌بازار: [دلایل خطای Blocked by Play Protect](https://developers.cafebazaar.ir/fa/app-publish-guidelines/academy/blocked-play-protect)

---

## علت‌های رایج (و وضعیت پوشه)

| علت | وضعیت پوشه |
|-----|------------|
| APK با کلید **debug** امضا شده | ✅ CI با keystore تولیدی امضا می‌کند |
| استفاده از **HTTP** به‌جای HTTPS | ✅ فقط `https://posheapp.ir/api/v1` — cleartext از اپ حذف شد |
| `targetSdk` پایین | ✅ targetSdk 36 |
| دسترسی‌های خطرناک (SMS، نصب، …) | ✅ فقط `INTERNET` |
| اپ ناشناس / امضای جدید | ⚠️ نیاز به **Play Protect Appeals** |
| نصب مستقیم از سایت (سایدلود) | ⚠️ تا تأیید گوگل، هشدار می‌دهد |

---

## اطلاعات اپ برای فرم Play Protect Appeals

فرم گوگل: **https://support.google.com/googleplay/android-developer/contact/protectappeals**

| فیلد | مقدار |
|------|--------|
| Developer email | Info@posheapp.ir |
| Developer name | Posheh / پوشه |
| Application package name | `ir.posheapp.posheh` |
| URL to download APK | `https://posheapp.ir/downloads/posheh-android.apk` |
| SHA-256 certificate fingerprint | `67:D6:81:81:6F:D9:4E:77:4F:A5:29:0F:31:CC:2E:98:BD:0E:50:EB:87:D7:8C:0F:2B:9F:51:62:93:FB:53:D6` |

### متن پیشنهادی (Additional information — انگلیسی)

```
Posheh is a legitimate cloud CRM and property management app for real estate consultants in Iran.

- Package: ir.posheapp.posheh
- Permissions: INTERNET only (no SMS, location, or install permissions)
- API: HTTPS only at https://posheapp.ir/api/v1
- Privacy policy: https://posheapp.ir/privacy
- Signed with our production release keystore (not debug)
- Distributed via Cafe Bazaar and our official website

The app is not malware. It requires network access for OTP login and cloud sync.
Please whitelist this app signature for Play Protect.
```

---

## مراحل رفع مشکل (گام‌به‌گام)

### ۱. مطمئن شو APK درست امضا شده

```bash
# روی سیستمی که Android SDK دارد:
apksigner verify --print-certs frontend/public/downloads/posheh-android.apk
```

باید **CN=Posheh, C=IR** ببینی — نه `Android Debug`.

اگر debug بود → GitHub Secrets `ANDROID_KEYSTORE_*` را چک کن و workflow **Build Android & Windows** را دوباره اجرا کن.

### ۲. از کافه‌بازار نصب کن (نه سایدلود)

کاربران و تیم بررسی بازار باید از **خود کافه‌بازار** نصب کنند، نه APK مستقیم از سایت. سایدلود همیشه احتمال هشدار Play Protect را دارد.

### ۳. فرم Play Protect Appeals را پر کن

1. لینک بالا را باز کن
2. ایمیل: `Info@posheapp.ir`
3. Package: `ir.posheapp.posheh`
4. لینک APK: `https://posheapp.ir/downloads/posheh-android.apk`
   - اگر فرم لینک را قبول نکرد، با [bitly.com](https://bitly.com) کوتاه کن
5. متن انگلیسی بالا را paste کن
6. **ارسال** — ۳ تا ۱۰ روز صبر کن

### ۴. بعد از ارسال درخواست

- ❌ **نام پکیج** (`ir.posheapp.posheh`) را عوض نکن
- ❌ **keystore** را عوض نکن
- ✅ فقط با **همان امضا** آپدیت بده
- ✅ نسخه جدید را در بازار آپلود کن

### ۵. برای کاربرانی که از سایت دانلود می‌کنند

تا تأیید گوگل:
- «نصب در هر صورت» / Install anyway
- یا از **کافه‌بازار** نصب کنند

---

## چک‌لیست قبل از آپلود در کافه‌بازار

- [ ] APK با keystore تولیدی (GitHub Actions)
- [ ] `targetSdk` ۳۴+ (فعلی: ۳۶)
- [ ] فقط permission `INTERNET`
- [ ] API فقط HTTPS
- [ ] لینک حریم خصوصی: https://posheapp.ir/privacy
- [ ] ایمیل توسعه‌دهنده: Info@posheapp.ir
- [ ] فرم Play Protect Appeals ارسال شده

---

## اگر باز هم بلاک شد

1. ۱۰–۱۴ روز صبر کن (اسکن خودکار گوگل)
2. دوباره Appeals بفرست با لینک کوتاه‌شده
3. وابستگی‌های غیرضروری را از `pubspec.yaml` حذف نکن مگر لازم باشد
4. با پشتیبانی کافه‌بازار تماس بگیر و SHA-256 بالا را بفرست

---

*آخرین به‌روزرسانی: نسخه ۱.۱.۰ — cleartext HTTP حذف شد*
