#!/bin/sh
# Clear Laravel + frontend caches and rebuild SPA (fixes stale login/register UI).
set -eu

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"
COMPOSE="docker compose"

echo "==> 1/5 Laravel caches"
$COMPOSE exec -T app sh -c 'rm -f bootstrap/cache/*.php 2>/dev/null || true'
$COMPOSE exec -T app php artisan optimize:clear --no-interaction
$COMPOSE exec -T app php artisan config:clear --no-interaction
$COMPOSE exec -T app php artisan cache:clear --no-interaction
$COMPOSE exec -T app php artisan route:clear --no-interaction
$COMPOSE exec -T app php artisan view:clear --no-interaction

echo "==> 2/5 Remove old frontend build"
rm -rf frontend/dist frontend/node_modules/.vite 2>/dev/null || true

echo "==> 3/5 Build frontend (fresh)"
(
  cd frontend
  if [ -f package-lock.json ]; then
    npm ci || npm install
  else
    npm install
  fi
  npm run build
)

echo "==> 4/5 Verify login page bundle (email/password UI)"
if grep -rq 'ورود با موبایل' frontend/dist/assets/*.js 2>/dev/null; then
  echo "WARNING: Old OTP login text still in bundle — check git branch"
else
  echo "OK: New login UI in dist"
fi

echo "==> 5/5 Reload nginx"
$COMPOSE exec -T nginx nginx -s reload 2>/dev/null || $COMPOSE restart nginx

echo ""
echo "Done. Hard-refresh browser: Ctrl+Shift+R"
echo "Login: /login  |  Register: /register"
