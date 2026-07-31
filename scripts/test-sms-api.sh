#!/bin/sh
# Test SMS API paths from this server (Edge vs JSPD webservice).
set -eu

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"
COMPOSE="docker compose"
MOBILE="${1:-09170577873}"
SEND="${2:-}"

if [ "$SEND" = "--send" ]; then
  $COMPOSE exec -T app php artisan system:sms-api-test "$MOBILE" --send --no-interaction
else
  $COMPOSE exec -T app php artisan system:sms-api-test "$MOBILE" --no-interaction
fi
