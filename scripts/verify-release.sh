#!/bin/sh
# Quick release verification (run on server after deploy)
set -eu
BASE="${1:-https://posheapp.ir}"

check() {
  url="$1"
  expect="$2"
  code=$(curl -s -o /dev/null -w "%{http_code}" "$url" || echo "000")
  if [ "$code" = "$expect" ]; then
    printf 'OK  %s → %s\n' "$code" "$url"
  else
    printf 'FAIL %s (expected %s) %s\n' "$code" "$expect" "$url"
    FAIL=1
  fi
}

FAIL=0
echo "==> Release checks for $BASE"
check "$BASE/api/v1/downloads" 200
check "$BASE/downloads/posheh-android.apk" 200
check "$BASE/downloads/posheh-windows.zip" 200
check "$BASE/blog/best-real-estate-crm-software-iran" 200
check "$BASE/blog/software-guide-1" 200
check "$BASE/sitemap.xml" 200
check "$BASE/manifest.json" 200

if curl -s "$BASE/blog/software-guide-1" | grep -q 'مقاله یافت نشد'; then
  echo "FAIL blog/software-guide-1 returns not-found page — run: docker compose exec app php artisan blog:seed --count=300 --force"
  FAIL=1
else
  echo "OK  blog/software-guide-1 has article content"
fi

if [ "$FAIL" = "1" ]; then
  exit 1
fi
echo "==> All checks passed"
