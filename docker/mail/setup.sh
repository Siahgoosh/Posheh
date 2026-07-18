#!/bin/bash
# One-time mailserver setup for Info@posheapp.ir
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
MAIL_DIR="$ROOT/docker/mail"
ENV_FILE="$MAIL_DIR/mailserver.env"

if [ ! -f "$ENV_FILE" ]; then
  cp "$MAIL_DIR/mailserver.env.example" "$ENV_FILE"
  echo "Created $ENV_FILE — edit passwords, then re-run."
  exit 1
fi

mkdir -p "$MAIL_DIR/config"

if [ -z "${MAIL_INFO_PASSWORD:-}" ]; then
  echo "Set MAIL_INFO_PASSWORD env var (password for Info@posheapp.ir), e.g.:"
  echo "  MAIL_INFO_PASSWORD='YourSecurePass123!' $0"
  exit 1
fi

COMPOSE="docker compose -f $ROOT/docker-compose.yml -f $ROOT/docker-compose.mail.yml"

$COMPOSE up -d mailserver
sleep 15

$COMPOSE exec -T mailserver setup email add Info@posheapp.ir "$MAIL_INFO_PASSWORD" || true
$COMPOSE exec -T mailserver setup alias add support@posheapp.ir Info@posheapp.ir || true
$COMPOSE exec -T mailserver setup config dkim || true

echo ""
echo "Mail accounts ready:"
echo "  Info@posheapp.ir"
echo "  support@posheapp.ir -> Info@posheapp.ir"
echo ""
echo "Add DNS records (see docs/EMAIL-SETUP.md), then configure backend/.env SMTP."
