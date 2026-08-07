# پوشه — اپلیکیشن موبایل و دسکتاپ

Flutter app for **Android** and **Windows**, synced with the web app at `https://posheapp.ir`.

**Version:** 1.0.3+8 · **Package:** `ir.posheapp.posheh`

## Features (synced with web)

- OTP login, dashboard, properties (list/create/detail)
- Owners, customers, visits, search, favorites
- CRM, accounting, reports, commissions, contracts, team, tickets
- Subscription view + 48h solo trial badge
- Plan-based feature gating (same as web sidebar)
- Links to register, download, blog from Settings

## Build locally

```bash
cd mobile
flutter pub get
flutter analyze
flutter test
flutter build apk --release --dart-define=API_URL=https://posheapp.ir/api/v1
flutter build windows --release --dart-define=API_URL=https://posheapp.ir/api/v1
```

Or from repo root:

```bash
./scripts/build-releases.sh
```

## Download URLs (production)

| Platform | URL |
|----------|-----|
| Download page | https://posheapp.ir/download |
| Android APK | https://posheapp.ir/downloads/posheh-android.apk |
| Windows ZIP | https://posheapp.ir/downloads/posheh-windows.zip |

## Cafe Bazaar

See [`docs/CAFE-BAZAAR.md`](../docs/CAFE-BAZAAR.md) for full listing texts, screenshots, and submission checklist.

## CI (GitHub Actions)

Workflow: `.github/workflows/build-releases.yml`

- **Android APK** on `ubuntu-latest`
- **Windows ZIP** on `windows-latest`
- On `main` or manual dispatch: copies to `frontend/public/downloads/`

Manual trigger: GitHub → Actions → **Build Android & Windows** → Run workflow.
