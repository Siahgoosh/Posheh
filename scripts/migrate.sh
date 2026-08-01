#!/bin/sh
# Run Laravel migrations inside Docker (host may not have php CLI).
set -eu

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"
COMPOSE="docker compose"

if ! command -v docker >/dev/null 2>&1; then
  echo "ERROR: Docker not found. Install Docker or run migrations manually inside the app container."
  exit 1
fi

echo "==> Running migrations via Docker (docker compose exec app php artisan migrate)"
$COMPOSE exec -T app php artisan migrate --force --no-interaction "$@"
echo "Done."
