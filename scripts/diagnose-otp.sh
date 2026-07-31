#!/bin/sh
set -eu

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

COMPOSE="docker compose"

echo "==> OTP / SMS diagnosis"
$COMPOSE exec -T app php artisan otp:diagnose "$@" --no-interaction

echo ""
echo "==> Full SMS probe"
$COMPOSE exec -T app php artisan system:sms-probe --no-interaction 2>/dev/null || true

echo ""
echo "==> Laravel log (OTP lines)"
$COMPOSE exec -T app sh -c "grep -i 'OTP SMS' storage/logs/laravel.log 2>/dev/null | tail -20 || echo '(no OTP lines in laravel.log)'"

echo ""
echo "==> Quick live SMS test (edit mobile in script if needed)"
echo "Run: docker compose exec app php artisan otp:send-sms 09170577873 123456"
