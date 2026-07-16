#!/bin/bash
# Render Cafe Bazaar screenshot HTML templates to PNG (1080x1920)
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
SRC="$ROOT/docs/store-assets/screenshots"
OUT="$ROOT/docs/store-assets/png"
mkdir -p "$OUT"

CHROME=""
for c in google-chrome google-chrome-stable chromium chromium-browser; do
  if command -v "$c" >/dev/null 2>&1; then CHROME="$c"; break; fi
done

if [ -z "$CHROME" ]; then
  echo "Chrome/Chromium not found. Open HTML files manually and screenshot at 1080x1920:"
  ls "$SRC"/*.html
  exit 0
fi

for html in "$SRC"/[0-9]*.html; do
  name="$(basename "$html" .html)"
  echo "Rendering $name..."
  "$CHROME" --headless=new --disable-gpu --window-size=1080,1920 \
    --screenshot="$OUT/${name}.png" "file://$html" 2>/dev/null
done

echo "Done → $OUT"
ls -lh "$OUT"/*.png 2>/dev/null || true
