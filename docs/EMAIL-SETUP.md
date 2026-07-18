# راه‌اندازی ایمیل Info@posheapp.ir

ایمیل رسمی: **Info@posheapp.ir**  
پشتیبانی: **support@posheapp.ir** (می‌تواند به همان صندوق forward شود)

---

## روش ۱ — پیشنهادی: Zoho Mail (رایگان، پایدار)

ساده‌ترین راه برای دامنه `posheapp.ir` بدون دردسر سرور ایمیل.

### گام‌ها

1. برو به [zoho.com/mail](https://www.zoho.com/mail/) → **Sign Up** → Plan رایگان (تا ۵ کاربر)
2. **Add Domain** → `posheapp.ir`
3. Zoho رکوردهای DNS را نشان می‌دهد — در پنل دامنه (ایران‌سرور، نیک‌نیم، Cloudflare، …) اضافه کن:

| نوع | نام | مقدار |
|-----|-----|--------|
| TXT | `@` | `zoho-verification=...` (از Zoho) |
| MX | `@` | `mx.zoho.com` اولویت 10 |
| MX | `@` | `mx2.zoho.com` اولویت 20 |
| TXT | `@` | `v=spf1 include:zoho.com ~all` |
| TXT | `zmail._domainkey` | DKIM از پنل Zoho |
| TXT | `_dmarc` | `v=DMARC1; p=none; rua=mailto:Info@posheapp.ir` |

4. در Zoho کاربر بساز: **Info@posheapp.ir**
5. (اختیاری) Alias: `support@posheapp.ir` → forward به Info

### اتصال به Laravel (سرور پوشه)

در `backend/.env` روی سرور:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.zoho.com
MAIL_PORT=587
MAIL_USERNAME=Info@posheapp.ir
MAIL_PASSWORD=رمز-اپلیکیشن-zoho
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=Info@posheapp.ir
MAIL_FROM_NAME="پوشه"
BACKUP_EMAIL=hamidrezakeshavarziii9@gmail.com
```

**نکته Zoho:** اگر 2FA داری، از [App Password](https://help.zoho.com/portal/en/kb/bigin/channels/email/articles/app-specific-password) استفاده کن.

تست:
```bash
docker compose exec app php artisan tinker
>>> Mail::raw('تست ایمیل پوشه', fn($m) => $m->to('hamidrezakeshavarziii9@gmail.com')->subject('Test'));
```

---

## روش ۲ — سرور ایمیل روی همان VPS (Docker)

فایل‌های آماده در `docker/mail/`. فقط اگر IP سرور reverse DNS دارد و پورت 25 باز است.

### پیش‌نیاز DNS

| نوع | نام | مقدار |
|-----|-----|--------|
| A | `mail` | IP سرور |
| MX | `@` | `mail.posheapp.ir` اولویت 10 |
| TXT | `@` | `v=spf1 mx a ip4:IP_SERVER ~all` |
| TXT | `_dmarc` | `v=DMARC1; p=quarantine; rua=mailto:Info@posheapp.ir` |

بعد از بالا آوردن mailserver، DKIM را از لاگ/setup بگیر و به DNS اضافه کن.

### راه‌اندازی

```bash
cd /var/www/posheh
cp docker/mail/mailserver.env.example docker/mail/mailserver.env
# ویرایش: DMS_DOMAIN=posheapp.ir و رمزها

./docker/mail/setup.sh
docker compose -f docker-compose.yml -f docker-compose.mail.yml up -d mailserver
```

### Laravel با mailserver محلی

```env
MAIL_MAILER=smtp
MAIL_HOST=mailserver
MAIL_PORT=587
MAIL_USERNAME=Info@posheapp.ir
MAIL_PASSWORD=رمز-تعیین‌شده-در-setup
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=Info@posheapp.ir
MAIL_FROM_NAME="پوشه"
```

---

## روش ۳ — cPanel / هاست اشتراکی

اگر دامنه روی cPanel است:

1. **Email Accounts** → Create → `Info@posheapp.ir`
2. SMTP: `mail.posheapp.ir` پورت 587 TLS
3. همان تنظیمات `.env` بالا با `MAIL_HOST=mail.posheapp.ir`

---

## کجاها از Info@posheapp.ir استفاده می‌شود

| محل | کاربرد |
|-----|--------|
| کافه‌بازار | ایمیل توسعه‌دهنده |
| Play Protect Appeals | ایمیل تماس |
| `MAIL_FROM` لاراول | OTP، بک‌آپ، اعلان‌ها |
| سایت | صفحه تماس / فوتر |
| `support@posheapp.ir` | تیکت پشتیبانی (می‌تواند alias باشد) |

---

## عیب‌یابی

| مشکل | راه‌حل |
|------|--------|
| ایمیل ارسال نمی‌شود | `MAIL_MAILER=log` را به `smtp` عوض کن؛ پورت 587 را چک کن |
| اسپم می‌شود | SPF + DKIM + DMARC را کامل کن |
| Zoho verify نمی‌شود | ۲۴–۴۸ ساعت صبر کن؛ TTL DNS را ۳۰۰ بگذار |
| بک‌آپ ایمیل نمی‌رسد | `BACKUP_EMAIL` و SMTP را تست کن |

---

*پس از تنظیم، حتماً یک ایمیل تست بفرست و در Spam هم چک کن.*
