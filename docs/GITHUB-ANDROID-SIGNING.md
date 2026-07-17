# امضای release اندروید با GitHub Actions (کافه‌بازار)

همه خروجی‌های انتشار (APK اندروید، ZIP ویندوز) از workflow **Build Android & Windows** ساخته می‌شوند. برای کافه‌بازار APK باید با **keystore تولیدی** امضا شود — نه debug.

---

## مرحله ۱ — یک‌بار: ساخت Keystore در GitHub

1. در GitHub بروید به **Actions**
2. workflow **Generate Android Keystore** را انتخاب کنید
3. **Run workflow** → Branch: `main`
4. رمزها را وارد کنید (مثلاً یک رمز قوی ۱۶+ کاراکتری — **حتماً ذخیره کنید**)
5. بعد از اتمام:
   - Artifact **posheh-release-keystore** را دانلود کنید
   - فایل `posheh-release.jks` را در جای امن نگه دارید (اگر گم شود آپدیت کافه‌بازار ممکن نیست)

---

## مرحله ۲ — اضافه کردن Secrets به ریپو

**Settings** → **Secrets and variables** → **Actions** → **New repository secret**

| Secret | مقدار |
|--------|--------|
| `ANDROID_KEYSTORE_BASE64` | محتوای کامل فایل `keystore-base64.txt` (یک خط base64) |
| `ANDROID_KEYSTORE_PASSWORD` | رمز keystore |
| `ANDROID_KEY_PASSWORD` | رمز کلید (معمولاً همان رمز keystore) |
| `ANDROID_KEY_ALIAS` | `posheh` (یا aliasی که در مرحله ۱ زدید) |

---

## مرحله ۳ — ساخت APK امضا‌شده

1. **Actions** → **Build Android & Windows** → **Run workflow**
2. یا با push به `main` (اگر `mobile/` تغییر کرده باشد)
3. خروجی‌ها:
   - Artifact **posheh-android-apk** — دانلود مستقیم از Actions
   - روی `main`: فایل commit می‌شود در `frontend/public/downloads/posheh-android.apk`
   - URL سایت: https://posheapp.ir/downloads/posheh-android.apk

در log باید ببینید: `APK is release-signed.`

اگر `debug-signed` دیدید، Secrets را چک کنید.

---

## آپلود در کافه‌بازار

1. APK را از Actions یا `frontend/public/downloads/posheh-android.apk` بگیرید
2. پنل توسعه‌دهنده کافه‌بازار → اپ جدید / نسخه جدید
3. متن‌ها و اسکرین‌شات: `docs/store-assets/CAFE-BAZAAR-LISTING-PACK.md`

---

## نکات مهم

- **هرگز** `posheh-release.jks` یا `key.properties` را commit نکنید
- برای هر نسخه جدید فقط `version` در `mobile/pubspec.yaml` را بالا ببرید و workflow را اجرا کنید
- همان keystore را برای همه آپدیت‌های کافه‌بازار استفاده کنید
- workflow روی branch `main` بدون Secrets **خطا می‌دهد** تا APK debug روی production نرود

---

## ساخت محلی (اختیاری)

```bash
export ANDROID_KEYSTORE_BASE64="$(base64 -w0 /path/to/posheh-release.jks)"
export ANDROID_KEYSTORE_PASSWORD='...'
export ANDROID_KEY_PASSWORD='...'
export ANDROID_KEY_ALIAS='posheh'
./scripts/build-releases.sh
```

یا `mobile/android/key.properties` + فایل `.jks` طبق `mobile/android/key.properties.example`
