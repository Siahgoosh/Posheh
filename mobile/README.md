# پوشه — اپلیکیشن موبایل و دسکتاپ

Flutter app for Android and Windows, connected to `https://posheapp.ir/api/v1`.

## Build locally

```bash
cd mobile
flutter pub get
flutter analyze
flutter test
flutter build apk --release --dart-define=API_URL=https://posheapp.ir/api/v1
flutter build windows --release --dart-define=API_URL=https://posheapp.ir/api/v1
```

## CI (GitHub Actions)

Workflow: `.github/workflows/build-releases.yml`

- **Android APK** on `ubuntu-latest`
- **Windows ZIP** on `windows-latest`
- Artifacts uploaded on every run
- On `main` or manual dispatch: copies to `frontend/public/downloads/`

Manual trigger: GitHub → Actions → **Build Android & Windows** → Run workflow.
