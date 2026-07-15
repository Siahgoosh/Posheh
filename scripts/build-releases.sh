#!/bin/bash
# Build release artifacts for Posheh
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
OUT="$ROOT/frontend/public/downloads"
mkdir -p "$OUT"

echo "Packaging Windows release..."
cd "$ROOT/frontend/public/downloads/posheh-windows"
zip -r "$OUT/posheh-windows.zip" . -x "*.DS_Store"

echo "Android: Flutter SDK required. Run: cd mobile && flutter build apk --release"
echo "Copy mobile/build/app/outputs/flutter-apk/app-release.apk to $OUT/posheh-android.apk"

if command -v flutter >/dev/null 2>&1; then
  cd "$ROOT/mobile"
  flutter pub get
  flutter build apk --release
  cp build/app/outputs/flutter-apk/app-release.apk "$OUT/posheh-android.apk"
  echo "Android APK built."
else
  echo "Flutter not found — skipping APK build. Placeholder README created."
  echo "Posheh Android 1.0.0 — build with: cd mobile && flutter build apk" > "$OUT/posheh-android.apk.txt"
fi

echo "Done. Files in $OUT"
