#!/usr/bin/env bash
# Idempotent Cloud Agent dependency bootstrap for Posheh (Laravel + React).
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

need_cmd() {
  if ! command -v "$1" >/dev/null 2>&1; then
    echo "[cloud-agent-install] missing required command: $1" >&2
    exit 1
  fi
}

need_cmd php
need_cmd composer
need_cmd node
need_cmd npm

php -r 'foreach (["pdo_sqlite","mbstring","tokenizer","xml","ctype","json","bcmath","curl","fileinfo","gd"] as $e) { if (!extension_loaded($e)) { fwrite(STDERR, "missing php extension: $e\n"); exit(1);} }'

echo "[cloud-agent-install] PHP $(php -r 'echo PHP_VERSION;') · Node $(node -v) · Composer $(composer -V 2>/dev/null | head -1)"

# Backend
(
  cd "$ROOT/backend"
  if [ ! -f .env ]; then
    cp .env.example .env
    # Local agent defaults: sqlite, no redis required for artisan/tests
    php -r '
      $p=".env";
      $e=file_get_contents($p);
      $e=preg_replace("/^APP_ENV=.*/m","APP_ENV=local",$e);
      $e=preg_replace("/^APP_DEBUG=.*/m","APP_DEBUG=true",$e);
      $e=preg_replace("/^DB_CONNECTION=.*/m","DB_CONNECTION=sqlite",$e);
      $e=preg_replace("/^DB_HOST=.*/m","# DB_HOST=127.0.0.1",$e);
      $e=preg_replace("/^#? *DB_DATABASE=.*/m","DB_DATABASE=".getcwd()."/database/database.sqlite",$e);
      $e=preg_replace("/^CACHE_STORE=.*/m","CACHE_STORE=file",$e);
      $e=preg_replace("/^QUEUE_CONNECTION=.*/m","QUEUE_CONNECTION=sync",$e);
      $e=preg_replace("/^SESSION_DRIVER=.*/m","SESSION_DRIVER=file",$e);
      $e=preg_replace("/^REDIS_CLIENT=.*/m","REDIS_CLIENT=predis",$e);
      file_put_contents($p,$e);
    '
  fi
  mkdir -p database storage/framework/{cache,sessions,views} storage/logs bootstrap/cache
  touch database/database.sqlite
  composer install --no-interaction --prefer-dist --no-ansi
  php artisan key:generate --force --no-interaction >/dev/null 2>&1 || true
  php artisan package:discover --ansi >/dev/null 2>&1 || true
)

# Frontend
(
  cd "$ROOT/frontend"
  if [ -f package-lock.json ]; then
    npm ci --no-audit --no-fund
  else
    npm install --no-audit --no-fund
  fi
)

echo "[cloud-agent-install] done"
