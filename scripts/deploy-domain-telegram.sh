#!/bin/sh
# Deploy domain (.ir) + Telegram bot features to production.
# PHP runs inside Docker — never run `php artisan` on the host.
set -eu

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"
BRANCH="${1:-main}"

log() { printf '\n==> %s\n' "$1"; }

log "Deploying domain + Telegram features from branch: $BRANCH"

if [ -x "$ROOT/scripts/deploy.sh" ]; then
  exec "$ROOT/scripts/deploy.sh" "$BRANCH"
fi

echo "ERROR: scripts/deploy.sh not found"
exit 1
