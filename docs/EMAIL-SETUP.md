# راه‌اندازی ایمیل Info@posheapp.ir — Cloudflare + Mailu

پنل کامل ایمیل با **وب‌میل**، **پنل ادمین**، SMTP و IMAP روی سرور خودت.

| آدرس | کاربرد |
|------|--------|
| **https://mail.posheapp.ir/webmail** | خواندن/ارسال ایمیل (Roundcube) |
| **https://mail.posheapp.ir/admin** | مدیریت کاربران، alias، DKIM |
| **Info@posheapp.ir** | ایمیل اصلی |
| **support@posheapp.ir** | alias به Info (خودکار ساخته می‌شود) |

---

## مرحله ۱ — DNS در Cloudflare

برو به [dash.cloudflare.com](https://dash.cloudflare.com) → دامنه **posheapp.ir** → **DNS** → **Records**

### رکوردهای الزامی

| نوع | Name | Content | Proxy | TTL |
|-----|------|---------|-------|-----|
| **A** | `mail` | `IP سرور تو` | **DNS only** (ابر خاکستری) | Auto |
| **MX** | `@` | `mail.posheapp.ir` | — | priority **10** |
| **TXT** | `@` | `v=spf1 mx a:mail.posheapp.ir ~all` | — | Auto |
| **TXT** | `_dmarc` | `v=DMARC1; p=none; rua=mailto:Info@posheapp.ir` | — | Auto |

### نکات Cloudflare

1. رکورد **mail** حتماً **Proxied خاموش** (خاکستری) — برای SMTP/IMAP لازم است
2. رکورد **MX** و **SPF** روی ریشه `@` باشد
3. رکورد **DKIM** بعد از deploy از پنل Mailu می‌گیری (مرحله ۴)

### SSL در Cloudflare (اختیاری ولی توصیه‌شده)

برای `mail.posheapp.ir`:
- **SSL/TLS** → Overview → **Full** یا **Full (strict)**
- اگر Certificate روی سرور نداری، موقتاً **Flexible** هم کار می‌کند

---

## مرحله ۲ — Deploy روی سرور

```bash
cd /var/www/posheh

# ۱. کد جدید
./scripts/deploy.sh cursor/zibal-payments-seo-backup-e117

# ۲. رمز ایمیل را تنظیم کن (فقط یک‌بار)
cp docker/mail/secrets.env.example docker/mail/secrets.env
nano docker/mail/secrets.env
# MAIL_INFO_PASSWORD=رمز-تو

# ۳. راه‌اندازی Mailu
chmod +x scripts/setup-mail.sh
./scripts/setup-mail.sh

# ۴. بالا آوردن nginx با پنل ایمیل
docker compose -f docker-compose.yml -f docker-compose.mail.yml up -d
docker compose restart nginx
```

### یک خط (بعد از ساخت secrets.env)

```bash
./scripts/fix-site-and-mail.sh
```

یا جداگانه:

```bash
./scripts/recover-site.sh
./scripts/setup-mail.sh
```

---

## مرحله ۳ — ورود به پنل

| | |
|--|--|
| **وب‌میل** | https://mail.posheapp.ir/webmail |
| **ادمین** | https://mail.posheapp.ir/admin |
| **کاربر** | `Info@posheapp.ir` |
| **رمز** | همان `MAIL_INFO_PASSWORD` در secrets.env |

از پنل ادمین می‌توانی:
- کاربر جدید بسازی
- alias اضافه کنی (مثلاً sales@)
- کوتا و فیلتر تنظیم کنی
- DKIM را ببینی

---

## مرحله ۴ — DKIM (بعد از اولین ورود)

1. برو **https://mail.posheapp.ir/admin**
2. **Mail domains** → `posheapp.ir` → **DKIM keys**
3. مقدار TXT را کپی کن
4. در Cloudflare اضافه کن:

| نوع | Name | Content |
|-----|------|---------|
| TXT | `dkim._domainkey` (یا همان نامی که Mailu نشان می‌دهد) | مقدار DKIM |

---

## مرحله ۵ — اتصال به اپ پوشه (Laravel)

`setup-mail.sh` خودکار `backend/.env` را تنظیم می‌کند:

```env
MAIL_MAILER=smtp
MAIL_HOST=mailu-front
MAIL_PORT=587
MAIL_USERNAME=Info@posheapp.ir
MAIL_PASSWORD=...
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=Info@posheapp.ir
MAIL_FROM_NAME=پوشه
BACKUP_EMAIL=hamidrezakeshavarziii9@gmail.com
```

تست ارسال:

```bash
docker compose exec app php artisan tinker
>>> Mail::raw('تست ایمیل پوشه', fn($m) => $m->to('hamidrezakeshavarziii9@gmail.com')->subject('Test Posheh'));
```

---

## اتصال از Outlook / Gmail / موبایل

| تنظیم | مقدار |
|-------|--------|
| IMAP Server | `mail.posheapp.ir` |
| IMAP Port | `993` SSL |
| SMTP Server | `mail.posheapp.ir` |
| SMTP Port | `587` STARTTLS |
| Username | `Info@posheapp.ir` |
| Password | رمز secrets.env |

---

## پورت‌های باز روی سرور (فایروال)

| پورت | سرویس |
|------|--------|
| 25 | SMTP دریافت |
| 587 | SMTP ارسال (STARTTLS) |
| 465 | SMTP SSL |
| 993 | IMAP SSL |
| 80/443 | وب‌میل و ادمین |

```bash
# Ubuntu UFW example:
sudo ufw allow 25,465,587,993/tcp
```

---

## عیب‌یابی

| مشکل | راه‌حل |
|------|--------|
| mail.posheapp.ir باز نمی‌شود | `docker compose ps` — mailu-front باید Up باشد؛ nginx restart |
| ایمیل ارسال نمی‌شود | SPF و DKIM را چک کن؛ پورت 587 باز باشد |
| اسپم می‌شود | DKIM + DMARC کامل کن؛ ۲۴ ساعت صبر |
| Laravel خطا می‌دهد | `docker compose exec app php artisan config:clear` |
| پسورد اشتباه | `nano docker/mail/secrets.env` و دوباره `./scripts/setup-mail.sh` |

لاگ Mailu:
```bash
docker compose -f docker-compose.yml -f docker-compose.mail.yml logs mailu-front --tail=50
```

---

## فایل‌های مهم

```
docker/mail/secrets.env      ← رمز (gitignore — روی سرور بساز)
docker/mail/mailu.env        ← تنظیمات Mailu (خودکار)
docker-compose.mail.yml      ← سرویس‌های Mailu
docker/nginx/mail.conf       ← پروکسی mail.posheapp.ir
scripts/setup-mail.sh        ← اسکریپت راه‌اندازی
```

---

*پس از deploy حتماً یک ایمیل تست بفرست و Inbox و Spam را چک کن.*
