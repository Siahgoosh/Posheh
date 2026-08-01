#!/bin/sh
# Seed 300 SEO blog articles (SSR-readable HTML, 500+ words each).
set -eu
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"
COMPOSE="docker compose"

echo "==> Seeding blog articles via Docker"
$COMPOSE exec -T app php artisan blog:seed --count=300 --force

echo ""
echo "==> Verify SSR (replace SLUG with a real slug from output)"
echo "curl -s https://posheapp.ir/blog/crm-guide-1 | grep -E '<h1>|<h2>|<h3>' | head -10"
echo ""
echo "==> Sitemap post count"
curl -s https://posheapp.ir/sitemap.xml 2>/dev/null | grep -c '/blog/' || true
