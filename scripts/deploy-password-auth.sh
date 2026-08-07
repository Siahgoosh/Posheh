#!/bin/sh
# One-shot deploy: password auth (main) + migrate + legacy admin + fresh frontend (no stale OTP UI).
set -eu

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"
COMPOSE="docker compose"
BRANCH="${1:-cursor/customer-communication-e117}"

log() { printf '\n==> %s\n' "$1"; }

log "1. Sync code from origin/$BRANCH"
git fetch origin "$BRANCH"
git checkout -B "$BRANCH" "origin/$BRANCH"
git reset --hard "origin/$BRANCH"

log "2. Verify auth/login exists in backend"
if ! grep -q "auth/login" backend/routes/api.php; then
  echo "ERROR: backend/routes/api.php has no /auth/login — wrong branch?"
  exit 1
fi

log "3. Laravel migrate + clear caches"
$COMPOSE exec -T app php artisan migrate --force --no-interaction
$COMPOSE exec -T app php artisan optimize:clear --no-interaction

log "4. Ensure platform admin + legacy users"
ADMIN_PASS="${SEED_ADMIN_PASSWORD:-Posheh@2026}"
$COMPOSE exec -T app php artisan auth:ensure-platform-admin --password="$ADMIN_PASS" 2>/dev/null \
  || log "ensure-platform-admin warning"
$COMPOSE exec -T app php artisan auth:setup-legacy-user \
  09170577873 info@posheapp.ir posheh --password="$ADMIN_PASS" 2>/dev/null \
  || log "Skip legacy user (may already updated)"

log "5. Fresh frontend build"
rm -rf frontend/dist frontend/node_modules/.vite
(
  cd frontend
  npm ci || npm install
  npm run build
)

if grep -rq 'ورود با موبایل' frontend/dist/assets/*.js 2>/dev/null; then
  echo "ERROR: Old OTP UI still in bundle!"
  exit 1
fi

log "6. Verify version.json"
cat frontend/dist/version.json

log "7. Test API capabilities"
CAP=$(curl -sS "http://localhost:8000/api/v1/auth/capabilities" || echo '{}')
echo "$CAP"
echo "$CAP" | grep -q 'password' || {
  echo "ERROR: /auth/capabilities missing password login — backend not updated"
  exit 1
}

log "8. Restart nginx (not just reload)"
$COMPOSE restart nginx

LOGIN_CODE=$(curl -sS -o /dev/null -w "%{http_code}" -X POST "http://localhost:8000/api/v1/auth/login" \
  -H "Content-Type: application/json" \
  -d '{"login":"test","password":"test"}' || echo "000")
echo "POST /auth/login HTTP $LOGIN_CODE (expect 422 validation, NOT 404)"

if [ "$LOGIN_CODE" = "404" ]; then
  echo "ERROR: /auth/login returns 404 — run migrate and check routes"
  exit 1
fi

cat <<EOF

✓ Deploy complete (password auth v2)

Login:  https://posheapp.ir/login
Test:   curl -s https://posheapp.ir/version.json
        curl -s https://posheapp.ir/api/v1/auth/capabilities

Admin:  info@posheapp.ir / posheh / password: $ADMIN_PASS
        (change with: SEED_ADMIN_PASSWORD='...' ./scripts/deploy-password-auth.sh)

Browser: Ctrl+Shift+R or incognito window
EOF
