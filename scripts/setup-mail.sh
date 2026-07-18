#!/bin/bash
# Setup Mailu email panel for posheapp.ir — run once before first mail deploy
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
MAIL_DIR="$ROOT/docker/mail"
SECRETS="$MAIL_DIR/secrets.env"
MAILU_ENV="$MAIL_DIR/mailu.env"
COMPOSE="docker compose -f $ROOT/docker-compose.yml -f $ROOT/docker-compose.mail.yml"

log() { printf '[mail-setup] %s\n' "$1"; }

# Load or create secrets
if [ ! -f "$SECRETS" ]; then
  if [ -n "${MAIL_INFO_PASSWORD:-}" ]; then
    cat > "$SECRETS" <<EOF
MAIL_INFO_PASSWORD=${MAIL_INFO_PASSWORD}
MAIL_ADMIN_ADDRESS=Info@posheapp.ir
MAIL_SUPPORT_ALIAS=support@posheapp.ir
EOF
    log "Created secrets.env from MAIL_INFO_PASSWORD env var"
  else
    log "ERROR: Create $SECRETS first:"
    log "  cp docker/mail/secrets.env.example docker/mail/secrets.env"
    log "  nano docker/mail/secrets.env"
    log "Or: MAIL_INFO_PASSWORD='your-pass' $0"
    exit 1
  fi
fi

# shellcheck disable=SC1090
source "$SECRETS"

if [ -z "${MAIL_INFO_PASSWORD:-}" ] || [ "$MAIL_INFO_PASSWORD" = "your-password-here" ]; then
  log "ERROR: Set MAIL_INFO_PASSWORD in $SECRETS"
  exit 1
fi

# Generate mailu.env from example
if [ ! -f "$MAILU_ENV" ]; then
  cp "$MAIL_DIR/mailu.env.example" "$MAILU_ENV"
fi

SECRET_KEY=$(openssl rand -hex 32)
# Update mailu.env values
sed -i "s|^SECRET_KEY=.*|SECRET_KEY=${SECRET_KEY}|" "$MAILU_ENV"
sed -i "s|^INITIAL_ADMIN_PW=.*|INITIAL_ADMIN_PW=${MAIL_INFO_PASSWORD}|" "$MAILU_ENV"

# Detect mailu docker subnet for SUBNET variable
SUBNET_LINE=$(docker network inspect posheh_mailu 2>/dev/null | grep -oP '"Subnet": "\K[^"]+' | head -1 || true)
if [ -z "$SUBNET_LINE" ]; then
  SUBNET_LINE="172.28.0.0/16"
fi
if grep -q '^SUBNET=' "$MAILU_ENV"; then
  sed -i "s|^SUBNET=.*|SUBNET=${SUBNET_LINE}|" "$MAILU_ENV"
else
  echo "SUBNET=${SUBNET_LINE}" >> "$MAILU_ENV"
fi

log "Starting Mailu containers..."
$COMPOSE up -d mailu-redis mailu-admin mailu-front mailu-imap mailu-smtp mailu-antispam mailu-webmail

log "Waiting for Mailu admin (up to 90s)..."
for i in $(seq 1 18); do
  if $COMPOSE exec -T mailu-admin flask mailu config-update 2>/dev/null; then
    break
  fi
  sleep 5
done

# Create support alias
SUPPORT_USER="${MAIL_SUPPORT_ALIAS%%@*}"
SUPPORT_USER="${SUPPORT_USER:-support}"
$COMPOSE exec -T mailu-admin flask mailu alias "${SUPPORT_USER}" posheapp.ir Info@posheapp.ir 2>/dev/null \
  || $COMPOSE exec -T mailu-admin flask mailu alias add "${SUPPORT_USER}" posheapp.ir Info 2>/dev/null \
  || log "Support alias: add manually in admin panel"

# Configure Laravel .env on server
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

log ""
log "=========================================="
log "  Mailu email panel is ready"
log "=========================================="
log "  Webmail:  https://mail.posheapp.ir/webmail"
log "  Admin:    https://mail.posheapp.ir/admin"
log "  Login:    Info@posheapp.ir"
log ""
log "  Next: Add Cloudflare DNS records (see docs/EMAIL-SETUP.md)"
log "  DKIM:   Admin panel -> Mail domains -> posheapp.ir -> DKIM"
log "=========================================="
