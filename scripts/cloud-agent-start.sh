#!/usr/bin/env bash
# Per-boot reconciliation for Cloud Agents (no long-running servers here).
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

# Ensure writable Laravel paths exist after snapshot/boot.
mkdir -p \
  backend/storage/framework/{cache,sessions,views} \
  backend/storage/logs \
  backend/bootstrap/cache \
  backend/database

if [ ! -f backend/database/database.sqlite ]; then
  touch backend/database/database.sqlite
fi

# Clear stale compiled caches that may point at another machine path.
rm -f backend/bootstrap/cache/config.php \
  backend/bootstrap/cache/routes-v7.php \
  backend/bootstrap/cache/events.php \
  backend/bootstrap/cache/services.php 2>/dev/null || true

if [ -f backend/artisan ] && [ -d backend/vendor ]; then
  (cd backend && php artisan config:clear --no-interaction >/dev/null 2>&1 || true)
fi

echo "[cloud-agent-start] ready"
