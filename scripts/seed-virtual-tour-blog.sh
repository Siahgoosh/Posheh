#!/bin/sh
# Seed 50 virtual-tour SEO articles + landing links (Docker — no host PHP)
set -eu
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"
docker compose exec -T app php artisan db:seed --class=VirtualTourBlogSeeder --force --no-interaction
echo "Done. Articles updated with casual copy and /r/* landing links."
