#!/bin/bash
# Fix Mailu admin/webmail restart loop (INITIAL_ADMIN_MODE + SUBNET mismatch)
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
MAIL_DIR="$ROOT/docker/mail"
MAILU_ENV="$MAIL_DIR/mailu.env"
COMPOSE="docker compose -f $ROOT/docker-compose.yml -f $ROOT/docker-compose.mail.yml"
EXPECTED_SUBNET="172.28.203.0/24"

log() { printf '[fix-mail] %s\n' "$1"; }

if [ ! -f "$MAILU_ENV" ]; then
  log "mailu.env missing — run ./scripts/setup-mail.sh first"
  exit 1
fi

set_kv() {
  local key="$1" val="$2"
  if grep -q "^${key}=" "$MAILU_ENV"; then
    sed -i "s|^${key}=.*|${key}=${val}|" "$MAILU_ENV"
  else
    echo "${key}=${val}" >> "$MAILU_ENV"
  fi
}

log "Patching mailu.env (INITIAL_ADMIN_MODE, SUBNET)..."
set_kv INITIAL_ADMIN_MODE ifmissing
set_kv SUBNET "$EXPECTED_SUBNET"
grep -q '^DISABLE_STATISTICS=' "$MAILU_ENV" || set_kv DISABLE_STATISTICS True
grep -q '^WEBROOT_REDIRECT=' "$MAILU_ENV" || set_kv WEBROOT_REDIRECT /webmail

ACTUAL_SUBNET=$(
  docker network inspect posheh_mailu -f '{{range .IPAM.Config}}{{.Subnet}}{{end}}' 2>/dev/null || true
)
if [ -n "$ACTUAL_SUBNET" ] && [ "$ACTUAL_SUBNET" != "$EXPECTED_SUBNET" ]; then
  log "Network subnet is $ACTUAL_SUBNET (expected $EXPECTED_SUBNET) — recreating mailu network..."
  $COMPOSE down mailu-front mailu-admin mailu-imap mailu-smtp mailu-antispam mailu-webmail mailu-redis 2>/dev/null || true
  docker network rm posheh_mailu 2>/dev/null || true
fi

log "Recreating Mailu stack..."
$COMPOSE up -d mailu-redis mailu-admin mailu-front mailu-imap mailu-smtp mailu-antispam mailu-webmail

sleep 10
log "Container status:"
$COMPOSE ps

log ""
log "Admin logs (last 20 lines):"
$COMPOSE logs mailu-admin --tail=20 2>/dev/null || true

log ""
log "Webmail logs (last 20 lines):"
$COMPOSE logs mailu-webmail --tail=20 2>/dev/null || true

log ""
log "If admin/webmail show Up (not Restarting), open https://mail.posheapp.ir/admin"
