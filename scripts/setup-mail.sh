#!/bin/bash
# Setup Mailu email panel for posheapp.ir
set -uo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
MAIL_DIR="$ROOT/docker/mail"
SECRETS="$MAIL_DIR/secrets.env"
MAILU_ENV="$MAIL_DIR/mailu.env"
COMPOSE="docker compose -f $ROOT/docker-compose.yml -f $ROOT/docker-compose.mail.yml"

log() { printf '[mail-setup] %s\n' "$1"; }

mailu_network() {
  $COMPOSE ps -q mailu-front 2>/dev/null | head -1 | xargs -r docker inspect -f '{{range $k,$v := .NetworkSettings.Networks}}{{$k}}{{"\n"}}{{end}}' 2>/dev/null | head -1
}

wait_admin() {
  log "Waiting for Mailu admin (max 2 min)..."
  local i
  for i in $(seq 1 24); do
    if timeout 10 $COMPOSE exec -T mailu-admin true 2>/dev/null; then
      if timeout 20 $COMPOSE exec -T mailu-admin flask mailu config-update 2>/dev/null; then
        log "Mailu admin is ready."
        return 0
      fi
    fi
    printf '.'
    sleep 5
  done
  echo ""
  log "Admin still starting — web panel may need 1-2 more minutes."
  log "Check: docker compose -f docker-compose.yml -f docker-compose.mail.yml logs mailu-admin --tail=20"
  return 0
}

if [ ! -f "$SECRETS" ]; then
  if [ -n "${MAIL_INFO_PASSWORD:-}" ]; then
    cat > "$SECRETS" <<EOF
MAIL_INFO_PASSWORD=${MAIL_INFO_PASSWORD}
MAIL_ADMIN_ADDRESS=Info@posheapp.ir
MAIL_SUPPORT_ALIAS=support@posheapp.ir
EOF
    log "Created secrets.env"
  else
    log "ERROR: cp docker/mail/secrets.env.example docker/mail/secrets.env && nano"
    exit 1
  fi
fi

# shellcheck disable=SC1090
source "$SECRETS"

if [ -z "${MAIL_INFO_PASSWORD:-}" ] || [ "$MAIL_INFO_PASSWORD" = "your-password-here" ]; then
  log "ERROR: Set MAIL_INFO_PASSWORD in $SECRETS"
  exit 1
fi

mkdir -p "$MAIL_DIR/overrides/roundcube"

if [ ! -f "$MAILU_ENV" ]; then
  cp "$MAIL_DIR/mailu.env.example" "$MAILU_ENV"
fi

# Only generate secret key on first setup
if grep -q 'CHANGE_ME_SETUP_SCRIPT' "$MAILU_ENV" 2>/dev/null; then
  SECRET_KEY=$(openssl rand -hex 32)
  sed -i "s|^SECRET_KEY=.*|SECRET_KEY=${SECRET_KEY}|" "$MAILU_ENV"
fi
sed -i "s|^INITIAL_ADMIN_PW=.*|INITIAL_ADMIN_PW=${MAIL_INFO_PASSWORD}|" "$MAILU_ENV"

log "Pulling Mailu images..."
$COMPOSE pull mailu-front mailu-admin mailu-imap mailu-smtp mailu-antispam mailu-webmail mailu-redis || log "Pull warning (continuing)"

log "Starting Mailu containers..."
$COMPOSE up -d mailu-redis mailu-admin mailu-front mailu-imap mailu-smtp mailu-antispam mailu-webmail

sleep 8

NET=$(mailu_network)
if [ -n "$NET" ]; then
  log "Connecting app + nginx to network: $NET"
  docker network connect "$NET" posheh-app 2>/dev/null || true
  docker network connect "$NET" posheh-nginx 2>/dev/null || true
else
  log "WARNING: mailu network not found yet — run again after containers are up"
fi

wait_admin

SUPPORT_USER="${MAIL_SUPPORT_ALIAS%%@*}"
SUPPORT_USER="${SUPPORT_USER:-support}"
timeout 15 $COMPOSE exec -T mailu-admin flask mailu alias "${SUPPORT_USER}" posheapp.ir Info@posheapp.ir 2>/dev/null \
  || log "Support alias: add manually in admin panel"

ENV_FILE="$ROOT/backend/.env"
if [ -f "$ENV_FILE" ]; then
  set_env() {
    local key="$1" val="$2"
    if grep -q "^${key}=" "$ENV_FILE"; then
      sed -i "s|^${key}=.*|${key}=${val}|" "$ENV_FILE"
    else
      echo "${key}=${val}" >> "$ENV_FILE"
    fi
  }
  set_env MAIL_MAILER smtp
  set_env MAIL_HOST mailu-front
  set_env MAIL_PORT 587
  set_env MAIL_USERNAME "Info@posheapp.ir"
  set_env MAIL_PASSWORD "$MAIL_INFO_PASSWORD"
  set_env MAIL_ENCRYPTION tls
  set_env MAIL_FROM_ADDRESS "Info@posheapp.ir"
  set_env MAIL_FROM_NAME "پوشه"
  log "Updated backend/.env mail settings"
fi

docker compose restart nginx app 2>/dev/null || true

log ""
log "=========================================="
log "  Mailu deploy finished"
log "  Webmail: https://mail.posheapp.ir/webmail"
log "  Admin:   https://mail.posheapp.ir/admin"
log "  Login:   Info@posheapp.ir"
log ""
log "  If panel not open yet, wait 1-2 min then refresh."
log "  Status:  docker compose -f docker-compose.yml -f docker-compose.mail.yml ps"
log "=========================================="
