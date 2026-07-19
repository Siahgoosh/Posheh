#!/bin/bash
# One-command server recovery when deploy/mail/network fails
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

log() { printf '[recover] %s\n' "$1"; }

log "1. Main stack up..."
docker compose up -d mysql redis app nginx queue scheduler

log "2. Migrate (inside Docker — NOT php on host)..."
docker compose exec -T app php artisan migrate --force --no-interaction

log "3. Mail (if secrets.env exists)..."
if [ -f docker/mail/secrets.env ]; then
  chmod +x scripts/fix-mail-restart.sh 2>/dev/null || true
  ./scripts/fix-mail-restart.sh || log "Mail fix skipped/warning"
else
  log "Skip mail — no docker/mail/secrets.env"
fi

log "4. Status:"
docker compose ps
docker compose -f docker-compose.yml -f docker-compose.mail.yml ps 2>/dev/null || true

log "Done. Site: curl -s -o /dev/null -w '%{http_code}\n' http://localhost:8000/api/v1/plans"
