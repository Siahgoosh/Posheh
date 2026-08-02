#!/bin/sh
# Emergency: stop using edge.ippanel.com (blocked from NL server) — force JSPD/MaxSMS panel mode.
# Safe to run on production without full redeploy.
set -eu

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"
COMPOSE="docker compose"
ENV_FILE="${ENV_FILE:-backend/.env}"

echo "==> Force IPPANEL_API_MODE=jspd in .env"
if grep -q '^IPPANEL_API_MODE=' "$ENV_FILE" 2>/dev/null; then
  sed -i 's/^IPPANEL_API_MODE=.*/IPPANEL_API_MODE=jspd/' "$ENV_FILE"
else
  echo 'IPPANEL_API_MODE=jspd' >> "$ENV_FILE"
fi

echo "==> Set DB: sms_mode=live, ippanel_api_mode=jspd"
$COMPOSE exec -T app php artisan tinker --execute="
\$s = app(\App\Services\Settings\SystemSettingsService::class);
\$s->set('sms_mode', 'live');
\$s->set('ippanel_api_mode', 'jspd');
Illuminate\Support\Facades\Cache::forget('system_settings');
echo 'OK';
" --no-interaction

echo "==> Clear caches"
$COMPOSE exec -T app php artisan config:clear --no-interaction
$COMPOSE exec -T app php artisan cache:clear --no-interaction

echo ""
echo "==> Current SMS status"
$COMPOSE exec -T app php artisan system:sms-enable --show --no-interaction

echo ""
echo "==> Test JSPD from container (should NOT timeout if MaxSMS works):"
$COMPOSE exec -T app php artisan system:sms-probe --no-interaction 2>/dev/null || true

echo ""
echo "Send test OTP:"
echo "  docker compose exec app php artisan system:sms-probe 09170577873 --send"
echo ""
echo "If JSPD times out, check ippanel.com connectivity from this server."
