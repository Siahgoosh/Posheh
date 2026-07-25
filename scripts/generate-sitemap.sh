#!/bin/sh
# Generate static sitemap.xml fallback (primary: https://posheapp.ir/sitemap.xml via Laravel)
set -eu
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
OUT="$ROOT/frontend/public/sitemap.xml"
API_URL="${SITEMAP_API_URL:-http://localhost:8000/api/v1/sitemap.xml}"

if command -v curl >/dev/null 2>&1; then
  if curl -sf "$API_URL" -o "$OUT.tmp" 2>/dev/null; then
    mv "$OUT.tmp" "$OUT"
    echo "[sitemap] Wrote $OUT from $API_URL"
    exit 0
  fi
fi

echo "[sitemap] API unavailable — keeping existing $OUT or run after deploy"
