#!/bin/bash
# One-command server recovery when deploy/mail/network fails
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

log() { printf '[recover] %s\n' "$1"; }

log "1. Ensure mailu network exists (never delete it)..."
chmod +x scripts/ensure-mailu-network.sh 2>/dev/null || true
if [ -f docker/mail/mailu.env ] || [ -f docker/mail/secrets.env ]; then
  ./scripts/ensure-mailu-network.sh
else
  docker network inspect posheh_mailu >/dev/null 2>&1 || docker network create posheh_mailu
fi

log "2. Main stack up..."
docker compose up -d mysql redis app nginx queue scheduler

log "3. Migrate (inside Docker — NOT php on host)..."
docker compose exec -T app php artisan migrate --force --no-interaction

log "4. Mail (if secrets.env exists)..."
if [ -f docker/mail/secrets.env ]; then
  chmod +x scripts/mail-up.sh scripts/fix-mail-restart.sh 2>/dev/null || true
  ./scripts/mail-up.sh || ./scripts/fix-mail-restart.sh || log "Mail fix skipped/warning"
else
  log "Skip mail — no docker/mail/secrets.env"
fi

log "5. Status:"
docker compose ps
docker compose -f docker-compose.yml -f docker-compose.mail.yml ps 2>/dev/null || true

log "Done. Site: curl -s -o /dev/null -w '%{http_code}\n' http://localhost:8000/api/v1/plans"
