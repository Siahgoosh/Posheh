#!/bin/sh
# Enable live SMS on server without admin panel access
set -eu
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

echo "==> Enabling live SMS mode..."
docker compose exec -T app php artisan system:sms-enable --live --from-env

echo ""
echo "==> Current status:"
docker compose exec -T app php artisan system:sms-enable --show

echo ""
echo "==> Send OTP test (optional — pass mobile as arg):"
MOBILE="${1:-09170577873}"
docker compose exec -T app php artisan system:sms-test "$MOBILE" --otp

echo ""
echo "If login still fails and sms_mode was log, try OTP code: 123456"
