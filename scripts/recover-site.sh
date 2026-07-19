#!/bin/bash
# Emergency: bring posheapp.ir back online (Cloudflare 521 fix)
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"
COMPOSE="docker compose"

log() { printf '[recover] %s\n' "$1"; }

log "Pulling latest fix..."
git fetch origin cursor/zibal-payments-seo-backup-e117 2>/dev/null || true
git checkout -B cursor/zibal-payments-seo-backup-e117 origin/cursor/zibal-payments-seo-backup-e117 2>/dev/null \
  || git pull origin cursor/zibal-payments-seo-backup-e117 2>/dev/null || true

log "Starting core services..."
chmod +x scripts/ensure-mailu-network.sh 2>/dev/null || true
if [ -f docker/mail/mailu.env ] || [ -f docker/mail/secrets.env ]; then
  ./scripts/ensure-mailu-network.sh
else
  docker network inspect posheh_mailu >/dev/null 2>&1 || docker network create posheh_mailu
fi
$COMPOSE up -d mysql redis app nginx queue scheduler

log "Testing nginx config..."
$COMPOSE exec -T nginx nginx -t

log "Restarting nginx..."
$COMPOSE restart nginx

sleep 3
HTTP=$(curl -s -o /dev/null -w "%{http_code}" http://localhost:8000/api/v1/plans || echo "000")
log "API status: $HTTP"

if [ "$HTTP" = "200" ] || [ "$HTTP" = "401" ]; then
  log "Site is UP. Check https://posheapp.ir"
else
  log "Still failing — run: docker compose logs nginx --tail=30"
  exit 1
fi
