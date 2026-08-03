#!/bin/sh
# Run Laravel migrations inside Docker (no host PHP required)
set -eu
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"
docker compose exec -T app php artisan migrate --force --no-interaction "$@"
