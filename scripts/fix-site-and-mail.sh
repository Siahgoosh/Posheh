#!/bin/bash
# Fix site + email in one command
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"
COMPOSE="docker compose -f docker-compose.yml -f docker-compose.mail.yml"

log() { printf '[fix-all] %s\n' "$1"; }

log "1/4 Recover website..."
"$ROOT/scripts/recover-site.sh" || docker compose up -d mysql redis app nginx queue scheduler

log "2/4 Setup email..."
if [ -f docker/mail/secrets.env ]; then
  "$ROOT/scripts/setup-mail.sh"
else
  log "Skip mail — create docker/mail/secrets.env first"
fi

log "3/4 Verify containers..."
docker compose ps
$COMPOSE ps 2>/dev/null || true

log "4/4 Health check..."
sleep 3
curl -s -o /dev/null -w "Site API: %{http_code}\n" http://localhost:8000/api/v1/plans || true
curl -s -o /dev/null -w "Mail panel: %{http_code}\n" -H "Host: mail.posheapp.ir" http://localhost:8000/admin || true

log "Done."
