#!/bin/bash
# Fix Mailu admin/webmail restart loop (INITIAL_ADMIN_MODE + SUBNET mismatch)
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
MAIL_DIR="$ROOT/docker/mail"
MAILU_ENV="$MAIL_DIR/mailu.env"
COMPOSE="docker compose -f $ROOT/docker-compose.yml -f $ROOT/docker-compose.mail.yml"
EXPECTED_SUBNET="172.28.203.0/24"
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

mailu_net_name() {
  $COMPOSE ps -q mailu-front 2>/dev/null | head -1 \
    | xargs -r docker inspect -f '{{range $k,$v := .NetworkSettings.Networks}}{{$k}}{{"\n"}}{{end}}' 2>/dev/null \
    | head -1
}

stop_mail_stack() {
  log "Stopping Mailu containers..."
  $COMPOSE stop "${MAIL_SERVICES[@]}" 2>/dev/null || true
  $COMPOSE rm -f "${MAIL_SERVICES[@]}" 2>/dev/null || true
}

log "1/5 Patching mailu.env..."
set_kv INITIAL_ADMIN_MODE ifmissing
set_kv SUBNET "$EXPECTED_SUBNET"
set_kv REDIS_ADDRESS mailu-redis
set_kv ADMIN_ADDRESS mailu-admin
set_kv FRONT_ADDRESS mailu-front
set_kv IMAP_ADDRESS mailu-imap
set_kv SMTP_ADDRESS mailu-smtp
set_kv ANTISPAM_ADDRESS mailu-antispam
set_kv WEBMAIL_ADDRESS mailu-webmail
grep -q '^DISABLE_STATISTICS=' "$MAILU_ENV" || set_kv DISABLE_STATISTICS True
grep -q '^WEBROOT_REDIRECT=' "$MAILU_ENV" || set_kv WEBROOT_REDIRECT /webmail

NET_NAME=$(mailu_net_name)
if [ -z "$NET_NAME" ]; then
  NET_NAME="posheh_mailu"
fi

ACTUAL_SUBNET=$(docker network inspect "$NET_NAME" -f '{{range .IPAM.Config}}{{.Subnet}}{{end}}' 2>/dev/null || true)

log "2/5 Network $NET_NAME subnet: ${ACTUAL_SUBNET:-not found} (want $EXPECTED_SUBNET)"
if [ -n "$ACTUAL_SUBNET" ] && [ "$ACTUAL_SUBNET" != "$EXPECTED_SUBNET" ]; then
  log "Subnet mismatch — recreating mailu network..."
  stop_mail_stack
  docker network disconnect "$NET_NAME" posheh-nginx 2>/dev/null || true
  docker network disconnect "$NET_NAME" posheh-app 2>/dev/null || true
  docker network rm "$NET_NAME" 2>/dev/null || true
fi

log "3/5 Starting Mailu (force-recreate all mail services for DNS aliases)..."
$COMPOSE up -d --force-recreate "${MAIL_SERVICES[@]}"

sleep 20

NET_NAME=$(mailu_net_name)
if [ -n "$NET_NAME" ]; then
  log "4/5 Connecting nginx + app to $NET_NAME..."
  docker network connect "$NET_NAME" posheh-nginx 2>/dev/null || true
  docker network connect "$NET_NAME" posheh-app 2>/dev/null || true
  docker compose restart nginx 2>/dev/null || true
fi

log "5/5 Status:"
$COMPOSE ps

ADMIN_STATE=$($COMPOSE ps mailu-admin --format '{{.State}}' 2>/dev/null || echo unknown)
WEBMAIL_STATE=$($COMPOSE ps mailu-webmail --format '{{.State}}' 2>/dev/null || echo unknown)
if echo "$ADMIN_STATE$WEBMAIL_STATE" | grep -qi restarting; then
  log ""
  log "Admin/webmail still restarting — last log lines:"
  $COMPOSE logs mailu-admin --tail=25 2>/dev/null || true
  $COMPOSE logs mailu-webmail --tail=25 2>/dev/null || true
  log ""
  log "Quick manual fix (if git pull not done yet):"
  log "  sed -i 's/^INITIAL_ADMIN_MODE=.*/INITIAL_ADMIN_MODE=ifmissing/' docker/mail/mailu.env"
  log "  grep -q '^INITIAL_ADMIN_MODE=' docker/mail/mailu.env || echo 'INITIAL_ADMIN_MODE=ifmissing' >> docker/mail/mailu.env"
  log "  docker compose -f docker-compose.yml -f docker-compose.mail.yml up -d --force-recreate mailu-admin mailu-webmail"
  exit 1
fi

log ""
log "Admin logs:"
$COMPOSE logs mailu-admin --tail=10 2>/dev/null || true
log ""
log "OK — open https://mail.posheapp.ir/admin and /webmail"
