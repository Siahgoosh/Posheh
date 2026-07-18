#!/bin/bash
# Setup Mailu email panel for posheapp.ir
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
MAIL_DIR="$ROOT/docker/mail"
SECRETS="$MAIL_DIR/secrets.env"
MAILU_ENV="$MAIL_DIR/mailu.env"
COMPOSE="docker compose -f $ROOT/docker-compose.yml -f $ROOT/docker-compose.mail.yml"

log() { printf '[mail-setup] %s\n' "$1"; }

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

SECRET_KEY=$(openssl rand -hex 32)
sed -i "s|^SECRET_KEY=.*|SECRET_KEY=${SECRET_KEY}|" "$MAILU_ENV"
sed -i "s|^INITIAL_ADMIN_PW=.*|INITIAL_ADMIN_PW=${MAIL_INFO_PASSWORD}|" "$MAILU_ENV"

# Ensure mailu docker network exists
docker network inspect posheh_mailu >/dev/null 2>&1 || docker network create posheh_mailu

SUBNET_LINE=$(docker network inspect posheh_mailu 2>/dev/null | grep -oP '"Subnet": "\K[^"]+' | head -1 || echo "172.28.0.0/16")
sed -i "s|^SUBNET=.*|SUBNET=${SUBNET_LINE}|" "$MAILU_ENV"

log "Pulling Mailu images (webmail not roundcube)..."
$COMPOSE pull mailu-front mailu-admin mailu-imap mailu-smtp mailu-antispam mailu-webmail mailu-redis

log "Starting Mailu..."
$COMPOSE up -d mailu-redis mailu-admin mailu-front mailu-imap mailu-smtp mailu-antispam mailu-webmail

log "Connecting app + nginx to mail network..."
docker network connect posheh_mailu posheh-app 2>/dev/null || true
docker network connect posheh_mailu posheh-nginx 2>/dev/null || true

log "Waiting for Mailu admin..."
for i in $(seq 1 24); do
  if $COMPOSE exec -T mailu-admin flask mailu config-update 2>/dev/null; then
    break
  fi
  sleep 5
done

SUPPORT_USER="${MAIL_SUPPORT_ALIAS%%@*}"
SUPPORT_USER="${SUPPORT_USER:-support}"
$COMPOSE exec -T mailu-admin flask mailu alias "${SUPPORT_USER}" posheapp.ir Info@posheapp.ir 2>/dev/null \
  || log "Add support alias manually in admin panel"

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
fi

$COMPOSE restart nginx app 2>/dev/null || true

log ""
log "=========================================="
log "  Mailu ready"
log "  Webmail: https://mail.posheapp.ir/webmail"
log "  Admin:   https://mail.posheapp.ir/admin"
log "  Login:   Info@posheapp.ir"
log "  DKIM:    Admin -> Mail domains -> posheapp.ir"
log "=========================================="
