#!/bin/sh
# Zero platform revenue counters (payments + wallet tx) — runs inside Docker
set -eu
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"
docker compose exec -T app php artisan platform:reset-revenue --force --no-interaction
