#!/bin/bash
# Start Mailu without touching the Docker network (safe for production deploys).
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
COMPOSE="docker compose -f $ROOT/docker-compose.yml -f $ROOT/docker-compose.mail.yml"
MAIL_SERVICES=(mailu-redis mailu-admin mailu-front mailu-imap mailu-smtp mailu-antispam mailu-webmail)

log() { printf '[mail-up] %s\n' "$1"; }

if [ ! -f "$ROOT/docker/mail/mailu.env" ]; then
  log "mailu.env missing — run ./scripts/setup-mail.sh first"
  exit 1
fi

"$ROOT/scripts/ensure-mailu-network.sh"

log "Starting Mailu (no network recreate, no force-recreate)..."
$COMPOSE up -d --no-recreate "${MAIL_SERVICES[@]}"

docker network connect posheh_mailu posheh-nginx 2>/dev/null || true
docker network connect posheh_mailu posheh-app 2>/dev/null || true
docker compose restart nginx 2>/dev/null || true

log "Done. Status:"
$COMPOSE ps
