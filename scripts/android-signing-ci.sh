#!/bin/sh
# Configure Android release signing from environment (GitHub Actions secrets or local export).
set -eu

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
ANDROID_DIR="${ANDROID_DIR:-$ROOT/mobile/android}"
KEYSTORE_PATH="$ANDROID_DIR/posheh-release.jks"
PROPS_PATH="$ANDROID_DIR/key.properties"

if [ -n "${ANDROID_KEYSTORE_BASE64:-}" ]; then
  mkdir -p "$ANDROID_DIR"
  printf '%s' "$ANDROID_KEYSTORE_BASE64" | base64 -d > "$KEYSTORE_PATH"

  if [ -z "${ANDROID_KEYSTORE_PASSWORD:-}" ] || [ -z "${ANDROID_KEY_PASSWORD:-}" ]; then
    echo "[ERROR] ANDROID_KEYSTORE_PASSWORD and ANDROID_KEY_PASSWORD are required with ANDROID_KEYSTORE_BASE64" >&2
    exit 1
  fi

  cat > "$PROPS_PATH" <<EOF
storePassword=${ANDROID_KEYSTORE_PASSWORD}
keyPassword=${ANDROID_KEY_PASSWORD}
keyAlias=${ANDROID_KEY_ALIAS:-posheh}
storeFile=../posheh-release.jks
EOF

  echo "Android release signing configured ($(basename "$KEYSTORE_PATH"))."
  exit 0
fi

if [ -f "$PROPS_PATH" ]; then
  echo "Using existing $PROPS_PATH"
  exit 0
fi

echo "WARNING: No release keystore — APK will be signed with Android debug key (not valid for Cafe Bazaar updates)."
