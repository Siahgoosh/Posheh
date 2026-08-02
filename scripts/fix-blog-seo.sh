#!/bin/sh
# Fix blog SEO: seed 300 articles + verify key slugs exist
set -eu
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"
COMPOSE="docker compose"
BASE="${1:-https://posheapp.ir}"

echo "==> Seeding 300 blog articles"
$COMPOSE exec -T app php artisan blog:seed --count=300 --force --no-interaction

echo "==> Verifying slugs in database"
$COMPOSE exec -T app php artisan tinker --execute="
\$slugs = ['software-guide-1', 'best-real-estate-crm-software-iran', 'crm-guide-1'];
foreach (\$slugs as \$s) {
    \$ok = App\Models\BlogPost::published()->where('slug', \$s)->exists();
    echo (\$ok ? 'OK' : 'MISSING') . \"  \$s\n\";
}
\$total = App\Models\BlogPost::published()->count();
echo \"Total published: \$total\n\";
"

echo "==> Sitemap blog URL for Google Search Console:"
echo "    $BASE/sitemap-blog.xml"
echo ""
echo "==> HTTP checks"
for slug in software-guide-1 best-real-estate-crm-software-iran; do
  if curl -sf "$BASE/blog/$slug" | grep -q 'مقاله یافت نشد'; then
    echo "FAIL $BASE/blog/$slug"
    exit 1
  fi
  echo "OK   $BASE/blog/$slug"
done

echo "==> Blog SEO fixed"
