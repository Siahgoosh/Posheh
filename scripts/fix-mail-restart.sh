#!/bin/bash
# Fix Mailu admin/webmail restart loop — safe (never deletes active Docker network)
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
MAIL_DIR="$ROOT/docker/mail"
MAILU_ENV="$MAIL_DIR/mailu.env"
COMPOSE="docker compose -f $ROOT/docker-compose.yml -f $ROOT/docker-compose.mail.yml"
MAIL_SERVICES=(mailu-redis mailu-admin mailu-front mailu-imap mailu-smtp mailu-antispam mailu-webmail)

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

sync_subnet_from_network() {
  local net="${1:-posheh_mailu}"
  local actual
  actual=$(docker network inspect "$net" -f '{{range .IPAM.Config}}{{.Subnet}}{{end}}' 2>/dev/null || true)
  if [ -n "$actual" ]; then
    set_kv SUBNET "$actual"
    log "SUBNET synced to existing network ($net): $actual"
  else
    set_kv SUBNET "172.28.203.0/24"
    log "Network not found — using default SUBNET 172.28.203.0/24"
  fi
}

log "1/4 Patching mailu.env..."
set_kv INITIAL_ADMIN_MODE ifmissing
sync_subnet_from_network "posheh_mailu"
set_kv REDIS_ADDRESS mailu-redis
set_kv ADMIN_ADDRESS mailu-admin
set_kv FRONT_ADDRESS mailu-front
set_kv IMAP_ADDRESS mailu-imap
set_kv SMTP_ADDRESS mailu-smtp
set_kv ANTISPAM_ADDRESS mailu-antispam
set_kv WEBMAIL_ADDRESS mailu-webmail
grep -q '^DISABLE_STATISTICS=' "$MAILU_ENV" || set_kv DISABLE_STATISTICS True
grep -q '^WEBROOT_REDIRECT=' "$MAILU_ENV" || set_kv WEBROOT_REDIRECT /webmail

log "2/4 Starting Mailu (force-recreate)..."
$COMPOSE up -d --force-recreate "${MAIL_SERVICES[@]}"

sleep 15

log "3/4 Connecting nginx + app to mailu network..."
docker network connect posheh_mailu posheh-nginx 2>/dev/null || true
docker network connect posheh_mailu posheh-app 2>/dev/null || true
docker compose restart nginx 2>/dev/null || true

log "4/4 Status:"
$COMPOSE ps

ADMIN_STATE=$($COMPOSE ps mailu-admin --format '{{.State}}' 2>/dev/null || echo unknown)
if echo "$ADMIN_STATE" | grep -qi restarting; then
  log "Admin still restarting:"
  $COMPOSE logs mailu-admin --tail=20 2>/dev/null || true
  exit 1
fi

log "OK — https://mail.posheapp.ir/admin"
