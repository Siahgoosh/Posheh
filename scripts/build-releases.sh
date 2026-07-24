#!/bin/bash
# Build release artifacts for Posheh (Android APK + Windows ZIP when possible)
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
OUT="$ROOT/frontend/public/downloads"
API_URL="${API_URL:-https://posheapp.ir/api/v1}"
mkdir -p "$OUT"

if ! command -v flutter >/dev/null 2>&1; then
  echo "Flutter not found. Install Flutter SDK first."
  exit 1
fi

cd "$ROOT/mobile"
flutter pub get
bash "$ROOT/scripts/patch-flutter-poolakey.sh"

bash "$ROOT/scripts/android-signing-ci.sh"

BAZAAR_DEFINE=()
if [[ -n "${BAZAAR_RSA_KEY:-}" ]]; then
  BAZAAR_DEFINE=(--dart-define=BAZAAR_RSA_KEY="$BAZAAR_RSA_KEY")
fi

echo "Building Android APK..."
flutter build apk --release --dart-define=API_URL="$API_URL" "${BAZAAR_DEFINE[@]}"
cp build/app/outputs/flutter-apk/app-release.apk "$OUT/posheh-android.apk"
echo "Android: $OUT/posheh-android.apk ($(du -h "$OUT/posheh-android.apk" | cut -f1))"

if [[ "$(uname -s)" == "MINGW"* || "$(uname -s)" == "MSYS"* || "$(uname -s)" == "Windows_NT" ]]; then
  echo "Building Windows release..."
  flutter build windows --release --dart-define=API_URL="$API_URL"
  WIN_SRC="build/windows/x64/runner/Release"
  rm -f "$OUT/posheh-windows.zip"
  (cd "$WIN_SRC" && zip -r "$OUT/posheh-windows.zip" . -x "*.DS_Store")
  echo "Windows: $OUT/posheh-windows.zip"
else
  echo "Windows build skipped (requires Windows host). CI builds posheh-windows.zip on windows-latest."
fi

echo "Done. Publish URLs:"
echo "  https://posheapp.ir/download"
echo "  https://posheapp.ir/downloads/posheh-android.apk"
