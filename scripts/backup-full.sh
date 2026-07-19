#!/bin/bash
# Full Posheh backup: database + storage uploads + env snapshot
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
STAMP="$(date +%Y-%m-%d_%H%M%S)"
OUT_DIR="${BACKUP_DIR:-$ROOT/backups}"
mkdir -p "$OUT_DIR"
ARCHIVE="$OUT_DIR/posheh-full-$STAMP.tar.gz"

log() { printf '[backup] %s\n' "$1"; }

DB_GZ=""
if command -v docker >/dev/null 2>&1 && [ -f "$ROOT/docker-compose.yml" ]; then
  log "Database dump via Docker..."
  DB_GZ="$OUT_DIR/posheh-db-$STAMP.sql.gz"
  docker compose -f "$ROOT/docker-compose.yml" exec -T app php artisan backup:database --no-email || true
  LATEST="$(ls -t "$ROOT/backend/storage/app/backups/"*.gz 2>/dev/null | head -1 || true)"
  if [ -n "$LATEST" ]; then
    cp "$LATEST" "$DB_GZ"
    log "DB backup: $DB_GZ"
  fi
elif [ -f "$ROOT/backend/artisan" ]; then
  log "Database dump via artisan..."
  (cd "$ROOT/backend" && php artisan backup:database --no-email) || true
  LATEST="$(ls -t "$ROOT/backend/storage/app/backups/"*.gz 2>/dev/null | head -1 || true)"
  [ -n "$LATEST" ] && DB_GZ="$LATEST"
fi

log "Archiving code + storage + downloads..."
tar -czf "$ARCHIVE" \
  --exclude='./mobile/build' \
  --exclude='./mobile/.dart_tool' \
  --exclude='./frontend/node_modules' \
  --exclude='./backend/vendor' \
  --exclude='./backups/*.tar.gz' \
  -C "$ROOT" \
  backend/app backend/config backend/database backend/routes backend/bootstrap backend/composer.json \
  backend/storage/app/public \
  frontend/public/downloads \
  frontend/src frontend/package.json \
  mobile/lib mobile/pubspec.yaml mobile/android \
  docker docker-compose.yml scripts docs \
  2>/dev/null || \
tar -czf "$ARCHIVE" \
  --exclude='mobile/build' \
  --exclude='mobile/.dart_tool' \
  --exclude='frontend/node_modules' \
  --exclude='backend/vendor' \
  --exclude='backups' \
  -C "$ROOT" .

if [ -n "$DB_GZ" ] && [ -f "$DB_GZ" ]; then
  log "Bundling DB into archive..."
  TMP="$OUT_DIR/.bundle-$STAMP"
  mkdir -p "$TMP"
  cp "$ARCHIVE" "$TMP/code.tar.gz"
  cp "$DB_GZ" "$TMP/database.sql.gz"
  [ -f "$ROOT/backend/.env" ] && cp "$ROOT/backend/.env" "$TMP/env.snapshot"
  tar -czf "${ARCHIVE%.tar.gz}-with-db.tar.gz" -C "$TMP" .
  rm -rf "$TMP"
  FINAL="${ARCHIVE%.tar.gz}-with-db.tar.gz"
else
  FINAL="$ARCHIVE"
fi

SIZE="$(du -h "$FINAL" | cut -f1)"
log "Done: $FINAL ($SIZE)"
log "Copy to safe storage before internet outage."
echo "$FINAL"
