#!/bin/sh
set -eu

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

BRANCH="${1:-cursor/final-platform-update-e117}"
COMPOSE="docker compose"

log() { printf '\n==> %s\n' "$1"; }
fail() { printf '\n[ERROR] %s\n' "$1" >&2; exit 1; }

wait_for_mysql() {
  log "Waiting for MySQL..."
  i=0
  while [ "$i" -lt 45 ]; do
    if $COMPOSE exec -T mysql mysqladmin ping -h localhost -uroot -p"${MYSQL_ROOT_PASSWORD:-secret}" --silent 2>/dev/null; then
      return 0
    fi
    if $COMPOSE exec -T mysql mysqladmin ping -h localhost -uroot -psecret --silent 2>/dev/null; then
      return 0
    fi
    i=$((i + 1))
    sleep 2
  done
  fail "MySQL is not ready. Run: docker compose logs mysql"
}

ensure_env_file() {
  ENV_FILE="$ROOT/backend/.env"

  if [ ! -f "$ENV_FILE" ]; then
    log "Creating backend/.env from example"
    cp "$ROOT/backend/.env.example" "$ENV_FILE"
  fi

  # Avoid gluing new keys to the last line when .env has no trailing newline.
  if [ -s "$ENV_FILE" ] && [ -n "$(tail -c 1 "$ENV_FILE" | tr -d '\n')" ]; then
    echo >> "$ENV_FILE"
  fi

  set_env_var() {
    _key="$1"
    _val="$2"
    if grep -q "^${_key}=" "$ENV_FILE" 2>/dev/null; then
      sed -i "s|^${_key}=.*|${_key}=${_val}|" "$ENV_FILE"
    else
      echo "${_key}=${_val}" >> "$ENV_FILE"
    fi
  }

  set_env_var CACHE_STORE file
  set_env_var QUEUE_CONNECTION sync
  set_env_var SESSION_DRIVER file
  set_env_var REDIS_HOST redis
  set_env_var REDIS_PORT 6379
  grep -q '^SMS_MODE=' "$ENV_FILE" || set_env_var SMS_MODE log
  grep -q '^SMS_PROVIDER=' "$ENV_FILE" || set_env_var SMS_PROVIDER maxsms
  grep -q '^IPPANEL_API_MODE=' "$ENV_FILE" || set_env_var IPPANEL_API_MODE jspd
  grep -q '^CAFE_BAZAAR_PACKAGE_NAME=' "$ENV_FILE" || set_env_var CAFE_BAZAAR_PACKAGE_NAME ir.posheapp.posheh
  grep -q '^CAFE_BAZAAR_SKU_SOLO=' "$ENV_FILE" || set_env_var CAFE_BAZAAR_SKU_SOLO solo01
  grep -q '^CAFE_BAZAAR_SKU_OFFICE=' "$ENV_FILE" || set_env_var CAFE_BAZAAR_SKU_OFFICE office01
  grep -q '^CAFE_BAZAAR_SKU_PREMIUM=' "$ENV_FILE" || set_env_var CAFE_BAZAAR_SKU_PREMIUM office02

  if [ -n "${CAFE_BAZAAR_API_TOKEN:-}" ]; then
    set_env_var CAFE_BAZAAR_API_TOKEN "$CAFE_BAZAAR_API_TOKEN"
    log "Cafe Bazaar API token applied from environment"
  fi

  grep -q '^DB_HOST=' "$ENV_FILE" || echo 'DB_HOST=mysql' >> "$ENV_FILE"
  grep -q '^DB_DATABASE=' "$ENV_FILE" || echo 'DB_DATABASE=posheh' >> "$ENV_FILE"
}

clear_laravel_cache() {
  $COMPOSE exec -T app sh -c 'rm -f bootstrap/cache/config.php bootstrap/cache/routes-v7.php bootstrap/cache/events.php bootstrap/cache/services.php 2>/dev/null || true'
  $COMPOSE exec -T app php artisan config:clear --no-interaction || true
  $COMPOSE exec -T app php artisan cache:clear --no-interaction || true
}

log "Posheh deploy — branch: $BRANCH"

if ! command -v docker >/dev/null 2>&1; then
  fail "Docker not found. Install Docker first."
fi

if ! docker compose version >/dev/null 2>&1; then
  fail "Docker Compose v2 not found. Install docker compose plugin."
fi

sync_code() {
  git fetch origin "$BRANCH" || fail "Could not fetch branch $BRANCH from origin"

  # Laravel/Docker runtime edits tracked .gitignore files under storage/ and bootstrap/cache/.
  # Local Flutter scaffolds under mobile/ can also block checkout on production servers.
  log "Resetting local runtime edits before checkout"
  git reset --hard HEAD 2>/dev/null || true
  git clean -fd -- mobile/ 2>/dev/null || true

  git checkout -B "$BRANCH" "origin/$BRANCH" || fail "Could not checkout $BRANCH"
  git reset --hard "origin/$BRANCH"
}

log "1/9 Fetching code"
sync_code

ensure_env_file

log "2/9 Starting containers"
$COMPOSE up -d --build || fail "docker compose up failed"

wait_for_mysql

log "3/9 Ensuring database exists"
$COMPOSE exec -T mysql mysql -uroot -p"${MYSQL_ROOT_PASSWORD:-secret}" -e \
  "CREATE DATABASE IF NOT EXISTS posheh CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" \
  2>/dev/null || $COMPOSE exec -T mysql mysql -uroot -psecret -e \
  "CREATE DATABASE IF NOT EXISTS posheh CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" \
  || fail "Could not create database"

clear_laravel_cache

log "4/9 Running migrations"
$COMPOSE exec -T app php artisan migrate --force --no-interaction \
  || fail "Migration failed — check: docker compose logs app"

clear_laravel_cache

log "5/9 Seeding settings, blog and demo data"
$COMPOSE exec -T app php artisan db:seed --class=SystemSettingsSeeder --force --no-interaction \
  || fail "SystemSettingsSeeder failed"
$COMPOSE exec -T app php artisan db:seed --class=BlogSeeder --force --no-interaction \
  || log "BlogSeeder warning (may already be seeded)"
$COMPOSE exec -T app php artisan db:seed --class=AppReleaseSeeder --force --no-interaction \
  || log "AppReleaseSeeder warning (may already be seeded)"
if [ "${SKIP_DEMO_SEED:-1}" = "1" ]; then
  log "Skipping demo office/users seeder (set SKIP_DEMO_SEED=0 to enable)"
else
  $COMPOSE exec -T app php artisan db:seed --class=DatabaseSeeder --force --no-interaction \
    || log "DatabaseSeeder warning (may already be seeded)"
fi

log "6/9 Clearing caches and enabling SMS"
clear_laravel_cache
$COMPOSE exec -T app php artisan optimize:clear --no-interaction || true
$COMPOSE exec -T app php artisan system:sms-enable --live --from-env --no-interaction 2>/dev/null \
  || log "Run manually: docker compose exec app php artisan system:sms-enable --live --from-env"
$COMPOSE exec -T app php artisan storage:link --force --no-interaction 2>/dev/null || true

log "7/9 Building frontend"
if [ ! -f frontend/package.json ]; then
  fail "frontend/package.json not found"
fi

(
  cd frontend
  if [ -f package-lock.json ]; then
    npm ci || npm install
  else
    npm install
  fi
  npm run build
) || fail "Frontend build failed — try: cd frontend && npm install && npm run build"

if [ ! -f frontend/dist/downloads/posheh-android.apk ] || [ "$(wc -c < frontend/dist/downloads/posheh-android.apk)" -lt 1000000 ]; then
  log "WARNING: posheh-android.apk missing or too small in dist — run ./scripts/build-releases.sh"
fi

log "8/9 Restarting services"
$COMPOSE restart app queue nginx scheduler 2>/dev/null || $COMPOSE restart app queue nginx

log "9/9 Health check"
sleep 6
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" http://localhost:8000/api/v1/plans || echo "000")
printf 'API /plans status: %s\n' "$HTTP_CODE"

if [ "$HTTP_CODE" = "200" ] || [ "$HTTP_CODE" = "401" ]; then
  log "Deploy successful"
else
  log "Deploy finished but API returned $HTTP_CODE"
  log "Debug: docker compose logs app --tail=50"
fi

cat <<EOF

Next steps:
  - Cafe Bazaar IAP: ./scripts/set-cafe-bazaar-env.sh 'JWT_FROM_PANEL'
    or: CAFE_BAZAAR_API_TOKEN='JWT' ./scripts/deploy.sh
  - After token change: docker compose exec app php artisan config:clear
  - Run scheduler: docker compose up -d scheduler
  - Seed contracts: docker compose exec app php artisan db:seed --class=ContractTemplateSeeder --force
  - OTP also needs: IPPANEL_USERNAME, IPPANEL_PASSWORD, IPPANEL_OTP_PATTERN_CODE=qhhly1nai3njev0
  - Enable SMS (if needed): docker compose exec app php artisan system:sms-enable --live --from-env
  - Test SMS:             docker compose exec app php artisan system:sms-test 09170577873 --otp --debug
  - Check SMS status:     docker compose exec app php artisan system:sms-enable
  - OTP logs:             docker compose exec app tail -50 storage/logs/laravel.log
  - Site URL:            http://YOUR_SERVER_IP/  (or :8000)
  - Admin settings:       /admin/settings
  - If sms_mode=log only: login OTP code is 123456

EOF
