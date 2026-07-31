#!/bin/sh
# One-command SMS fix for production (Netherlands server + IPPanel Edge API)
set -eu

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

COMPOSE="docker compose"

echo "==> SMS fix: live mode + edge API + sync .env"
$COMPOSE exec -T app php artisan system:sms-enable --fix --no-interaction

echo "==> Clear caches"
$COMPOSE exec -T app php artisan cache:clear --no-interaction
$COMPOSE exec -T app php artisan config:clear --no-interaction
$COMPOSE exec -T app php artisan queue:restart --no-interaction 2>/dev/null || true

echo ""
echo "==> Status"
$COMPOSE exec -T app php artisan system:sms-enable --show --no-interaction

echo ""
echo "==> Full probe"
$COMPOSE exec -T app php artisan system:sms-probe --no-interaction 2>/dev/null || true

echo ""
echo "==> Send test OTP (change mobile number):"
echo "    docker compose exec app php artisan system:sms-test 09170577873 --otp --debug"
echo ""
echo "    Or with probe:"
echo "    docker compose exec app php artisan system:sms-probe 09170577873 --send"
