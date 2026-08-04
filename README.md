# پوشه (Posheh) — سامانه ابری ثبت و مدیریت املاک

پلتفرم SaaS چندمستاجری برای دفاتر املاک ایرانی — فایلینگ، CRM، بازدید، تور مجازی ۳۶۰، وبسایت اختصاصی، اپ اندروید و ویندوز.

| | |
|---|---|
| **دامنه** | https://posheapp.ir |
| **پنل ادمین** | https://panel.posheapp.ir |
| **ریپو** | https://github.com/Siahgoosh/Posheh |
| **سرور production** | `/var/www/posheh` |

---

## شروع سریع برای Cursor (اکانت جدید)

```
فایل docs/CURSOR-HANDOFF-FA.md را بخوان — منبع حقیقت کل پروژه است.
```

---

## Deploy یکجا (آخرین نسخه)

```bash
cd /var/www/posheh
./scripts/deploy.sh cursor/release-deploy-e117
```

جزئیات: [docs/DEPLOY.md](docs/DEPLOY.md)

**نام شاخه:** با خط تیره `-` نه `/` — مثلاً `cursor/release-deploy-e117`

---

## ساختار پروژه

```
Posheh/
├── backend/                 # Laravel 12 API (PHP 8.4)
│   ├── app/
│   │   ├── Modules/VirtualTour/   # تور مجازی Enterprise
│   │   ├── Services/              # منطق کسب‌وکار
│   │   ├── Http/Controllers/
│   │   └── Models/
│   ├── database/migrations/
│   ├── routes/api.php
│   └── tests/
├── frontend/                # React 19 + TypeScript + Tailwind 4
│   ├── src/
│   │   ├── pages/           # صفحات پنل و لندینگ
│   │   ├── features/virtual-tour/
│   │   ├── components/
│   │   └── panel/           # پنل super-admin
│   └── public/downloads/    # APK و ZIP ویندوز
├── mobile/                  # Flutter — Android + Windows (کد مشترک)
├── docker/                  # Nginx, PHP, MySQL
├── scripts/
│   ├── deploy.sh            # Deploy اصلی
│   ├── build-releases.sh    # بیلد APK/ZIP
│   └── migrate.sh
└── docs/
    ├── CURSOR-HANDOFF-FA.md # ⭐ سند انتقال / ادامه کار
    ├── DEPLOY.md
    ├── VISIT-NOTIFICATIONS.md
    ├── VIRTUAL-TOUR.md
    ├── architecture/
    └── api/
```

---

## استک فنی

| لایه | تکنولوژی |
|------|----------|
| Backend | Laravel 12, MySQL 8.4, Redis 7 |
| Frontend | React 19, Vite, TanStack Query |
| Mobile/Desktop | Flutter 3.x (Android APK + Windows) |
| Infra | Docker Compose, Nginx |

---

## قابلیت‌های اصلی

- فایلینگ ملک، مالک، مشتری، CRM کانبان
- تقویم بازدید شمسی + نوتیف درخواست وبسایت (پنل + تلگرام)
- تور مجازی ۳۶۰ درجه (Photo Sphere Viewer)
- وبسایت اختصاصی `name.posheapp.ir`
- اشتراک، کیف پول، زیبال، کافه‌بازار
- ربات تلگرام دفتر
- PWA + اپ اندروید + ویندوز

---

## بیلد اندروید و ویندوز

```bash
./scripts/build-releases.sh
```

خروجی:
- `frontend/public/downloads/posheh-android.apk`
- `frontend/public/downloads/posheh-windows.zip`

CI: GitHub Actions → **Build Android & Windows** (روی push به `cursor/**` با تغییر `mobile/`)

---

## توسعه محلی

```bash
docker compose up -d
docker compose exec app php artisan migrate --seed
cd frontend && npm install && npm run dev
```

---

## مستندات

| سند | محتوا |
|-----|--------|
| [docs/CURSOR-HANDOFF-FA.md](docs/CURSOR-HANDOFF-FA.md) | انتقال پروژه، تاریخچه، env، PRها |
| [docs/DEPLOY.md](docs/DEPLOY.md) | Deploy و رفع خطای نام شاخه |
| [docs/VIRTUAL-TOUR.md](docs/VIRTUAL-TOUR.md) | تور مجازی |
| [docs/VISIT-NOTIFICATIONS.md](docs/VISIT-NOTIFICATIONS.md) | نوتیف بازدید |
| [docs/api/README.md](docs/api/README.md) | API |
| [mobile/README.md](mobile/README.md) | اپ Flutter |

---

## حساب دمو (محیط log SMS)

| نقش | موبایل | OTP |
|-----|--------|-----|
| Super Admin | 09120000000 | 123456 |
| مدیر دفتر | 09121111111 | 123456 |

---

## License

Proprietary — All rights reserved.
