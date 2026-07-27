#!/bin/sh
set -eu

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

COMPOSE="docker compose"

echo "==> Syncing IPPanel credentials from .env"
$COMPOSE exec -T app php artisan system:sms-enable --live --from-env --no-interaction

echo "==> Clearing caches"
$COMPOSE exec -T app php artisan cache:clear --no-interaction
$COMPOSE exec -T app php artisan config:clear --no-interaction

echo ""
echo "==> Current SMS status"
$COMPOSE exec -T app php artisan system:sms-enable --show --no-interaction

echo ""
echo "==> Test OTP SMS (replace mobile if needed)"
echo "    docker compose exec app php artisan system:sms-test 09170577873 --otp --debug"
echo ""
echo "After a successful test, login on the site will send real SMS codes."
