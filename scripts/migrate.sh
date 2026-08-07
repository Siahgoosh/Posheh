#!/bin/sh
# Run Laravel migrations inside Docker (host has no PHP — do not use: php artisan migrate)
set -eu
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"
docker compose exec -T app php artisan migrate --force --no-interaction "$@"
