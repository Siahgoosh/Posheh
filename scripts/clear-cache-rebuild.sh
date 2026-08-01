#!/bin/sh
# Clear Laravel + frontend caches and rebuild SPA (fixes stale login/register UI).
# NOTE: On production, PHP runs inside Docker — do NOT run `php artisan` on the host.
# Use: ./scripts/migrate.sh   or   ./scripts/deploy.sh main
set -eu

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"
COMPOSE="docker compose"

echo "==> 1/6 Laravel caches (via Docker)"
$COMPOSE exec -T app sh -c 'rm -f bootstrap/cache/*.php 2>/dev/null || true'
$COMPOSE exec -T app php artisan optimize:clear --no-interaction
$COMPOSE exec -T app php artisan config:clear --no-interaction
$COMPOSE exec -T app php artisan cache:clear --no-interaction
$COMPOSE exec -T app php artisan route:clear --no-interaction
$COMPOSE exec -T app php artisan view:clear --no-interaction

echo "==> 2/6 Run migrations (via Docker)"
if [ -x "$ROOT/scripts/migrate.sh" ]; then
  "$ROOT/scripts/migrate.sh" || echo "WARNING: migrate failed — run ./scripts/migrate.sh manually"
else
  $COMPOSE exec -T app php artisan migrate --force --no-interaction || true
fi

echo "==> 3/6 Remove old frontend build"
rm -rf frontend/dist frontend/node_modules/.vite 2>/dev/null || true

echo "==> 4/6 Build frontend (fresh)"
(
  cd frontend
  if [ -f package-lock.json ]; then
    npm ci || npm install
  else
    npm install
  fi
  npm run build
)

echo "==> 5/6 Verify login page bundle (email/password UI)"
if grep -rq 'ورود با موبایل' frontend/dist/assets/*.js 2>/dev/null; then
  echo "WARNING: Old OTP login text still in bundle — check git branch"
else
  echo "OK: New login UI in dist"
fi

echo "==> 6/6 Restart nginx (full restart)"
$COMPOSE restart nginx

echo "==> Verify API"
curl -sS "http://localhost:8000/api/v1/auth/capabilities" 2>/dev/null | head -c 200 || true
echo ""
echo "Done. Hard-refresh browser: Ctrl+Shift+R"
echo "Login: /login  |  Register: /register"
echo ""
echo "Tip: never run 'php artisan' on the host — use ./scripts/migrate.sh or ./scripts/deploy.sh main"
