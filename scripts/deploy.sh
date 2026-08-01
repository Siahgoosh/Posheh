#!/bin/sh
set -eu

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

BRANCH="${1:-main}"
COMPOSE="docker compose"
COMPOSE_MAIL="docker compose -f docker-compose.yml -f docker-compose.mail.yml"

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
  set_env_var QUEUE_CONNECTION redis
  set_env_var SESSION_DRIVER file
  set_env_var REDIS_HOST redis
  set_env_var REDIS_PORT 6379
  grep -q '^SMS_MODE=' "$ENV_FILE" || set_env_var SMS_MODE log
  grep -q '^SMS_PROVIDER=' "$ENV_FILE" || set_env_var SMS_PROVIDER maxsms
  grep -q '^IPPANEL_API_MODE=' "$ENV_FILE" || set_env_var IPPANEL_API_MODE jspd
  grep -q '^APP_TIMEZONE=' "$ENV_FILE" || set_env_var APP_TIMEZONE Asia/Tehran
  grep -q '^TELEGRAM_WEBHOOK_BASE_URL=' "$ENV_FILE" || set_env_var TELEGRAM_WEBHOOK_BASE_URL "https://posheapp.ir"
  grep -q '^TELEGRAM_WEBHOOK_FORCE_HTTPS=' "$ENV_FILE" || set_env_var TELEGRAM_WEBHOOK_FORCE_HTTPS true
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
  git restore backend/bootstrap/cache backend/storage 2>/dev/null \
    || git checkout -- backend/bootstrap/cache backend/storage 2>/dev/null \
    || true
  git reset --hard HEAD 2>/dev/null || true
  git clean -fd -- mobile/ 2>/dev/null || true

  git checkout -B "$BRANCH" "origin/$BRANCH" || fail "Could not checkout $BRANCH"
  git reset --hard "origin/$BRANCH"
}

log "1/10 Fetching code"
sync_code

if [ -x "$ROOT/scripts/ensure-mailu-network.sh" ]; then
  log "Ensuring Mailu Docker network exists"
  "$ROOT/scripts/ensure-mailu-network.sh" || log "Mailu network warning (email optional)"
fi

ensure_env_file

log "2/10 Starting containers"
$COMPOSE up -d --build || fail "docker compose up failed"

wait_for_mysql

log "3/10 Ensuring database exists"
$COMPOSE exec -T mysql mysql -uroot -p"${MYSQL_ROOT_PASSWORD:-secret}" -e \
  "CREATE DATABASE IF NOT EXISTS posheh CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" \
  2>/dev/null || $COMPOSE exec -T mysql mysql -uroot -psecret -e \
  "CREATE DATABASE IF NOT EXISTS posheh CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" \
  || fail "Could not create database"

clear_laravel_cache

log "4/10 Running migrations"
$COMPOSE exec -T app php artisan migrate --force --no-interaction \
  || fail "Migration failed — check: docker compose logs app"

clear_laravel_cache

log "5/10 Seeding settings, blog and demo data"
$COMPOSE exec -T app php artisan db:seed --class=SystemSettingsSeeder --force --no-interaction \
  || fail "SystemSettingsSeeder failed"
$COMPOSE exec -T app php artisan db:seed --class=BlogSeeder --force --no-interaction \
  || log "BlogSeeder warning (may already be seeded)"
$COMPOSE exec -T app php artisan blog:seed --count=300 --force --no-interaction 2>/dev/null \
  || log "Run ./scripts/seed-blog.sh to seed 300 SEO articles"
$COMPOSE exec -T app php artisan db:seed --class=VirtualTourSeeder --force --no-interaction 2>/dev/null \
  || log "VirtualTourSeeder skipped (virtual tour module not deployed yet)"
$COMPOSE exec -T app php artisan db:seed --class=AppReleaseSeeder --force --no-interaction \
  || log "AppReleaseSeeder warning (may already be seeded)"
if [ "${SKIP_DEMO_SEED:-1}" = "1" ]; then
  log "Skipping demo office/users seeder (set SKIP_DEMO_SEED=0 to enable)"
else
  $COMPOSE exec -T app php artisan db:seed --class=DatabaseSeeder --force --no-interaction \
    || log "DatabaseSeeder warning (may already be seeded)"
fi

log "6/10 Clearing caches and enabling SMS"
clear_laravel_cache
$COMPOSE exec -T app php artisan optimize:clear --no-interaction || true
if [ "${SMS_FORCE_LIVE:-0}" = "1" ]; then
  $COMPOSE exec -T app php artisan system:sms-enable --live --from-env --no-interaction 2>/dev/null \
    || log "Run manually: docker compose exec app php artisan system:sms-enable --live --from-env"
elif grep -qE '^SMS_MODE=live' "$ENV_FILE" 2>/dev/null; then
  $COMPOSE exec -T app php artisan system:sms-enable --live --from-env --no-interaction 2>/dev/null \
    || log "Run manually: docker compose exec app php artisan system:sms-enable --live --from-env"
else
  # Sync credentials only — do NOT reset sms_mode to log on every deploy
  $COMPOSE exec -T app php artisan system:sms-enable --from-env --no-interaction 2>/dev/null \
    || log "Run manually: docker compose exec app php artisan system:sms-enable --from-env"
fi
$COMPOSE exec -T app php artisan storage:link --force --no-interaction 2>/dev/null || true

log "7/10 Building frontend"
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

if [ ! -f frontend/dist/demo/sphere.jpg ]; then
  log "WARNING: frontend/dist/demo/sphere.jpg missing — virtual tour 360 images will not load"
fi

chmod +x "$ROOT/scripts/verify-panel-build.sh" 2>/dev/null || true
"$ROOT/scripts/verify-panel-build.sh" || fail "Panel build missing — panel.posheapp.ir needs frontend/dist/panel.html"

if [ ! -f frontend/dist/downloads/posheh-android.apk ] || [ "$(wc -c < frontend/dist/downloads/posheh-android.apk)" -lt 1000000 ]; then
  log "WARNING: posheh-android.apk missing or too small in dist — run ./scripts/build-releases.sh"
fi

log "8/10 Email (Mailu)"
if [ -f "$ROOT/docker/mail/secrets.env" ]; then
  if [ -x "$ROOT/scripts/fix-mail-restart.sh" ]; then
    "$ROOT/scripts/fix-mail-restart.sh" || log "Mail fix warning — try: ./scripts/setup-mail.sh"
  elif [ -x "$ROOT/scripts/setup-mail.sh" ]; then
    "$ROOT/scripts/setup-mail.sh" || log "Mail setup warning"
  fi
else
  log "Skip mail — create docker/mail/secrets.env then run ./scripts/setup-mail.sh"
fi

log "9/10 Restarting services"
$COMPOSE up -d redis queue app nginx scheduler 2>/dev/null || $COMPOSE up -d redis queue app nginx
$COMPOSE exec -T app php artisan queue:restart --no-interaction 2>/dev/null || true
$COMPOSE restart app queue nginx scheduler 2>/dev/null || $COMPOSE restart app queue nginx

log "10/10 Health check"
sleep 6
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" http://localhost:8000/api/v1/plans || echo "000")
printf 'API /plans status: %s\n' "$HTTP_CODE"

DEMO_CODE=$(curl -s -o /dev/null -w "%{http_code}" http://localhost:8000/demo/sphere.jpg || echo "000")
printf 'Demo panorama /demo/sphere.jpg: %s\n' "$DEMO_CODE"

OTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" -X POST http://localhost:8000/api/v1/auth/otp/send \
  -H "Content-Type: application/json" -H "Accept: application/json" \
  -d '{"mobile":"09120000000","purpose":"login"}' || echo "000")
printf 'OTP /auth/otp/send: %s\n' "$OTP_CODE"

REDIS_PING=$($COMPOSE exec -T redis redis-cli ping 2>/dev/null || echo "FAIL")
printf 'Redis ping: %s\n' "$REDIS_PING"
QUEUE_RUNNING=$($COMPOSE ps --status running --services 2>/dev/null | grep -c '^queue$' || echo "0")
printf 'Queue worker running: %s\n' "$QUEUE_RUNNING"

PANEL_HTML=$(curl -s -H "Host: panel.posheapp.ir" http://localhost:8000/ | head -c 500 || true)
if echo "$PANEL_HTML" | grep -q 'پنل مدیریت پلتفرم\|__POSHEH_PANEL__'; then
  printf 'Panel subdomain: admin app detected\n'
else
  log "Panel check: rebuild frontend if panel.posheapp.ir still shows landing"
fi

MAIL_CODE=$(curl -s -o /dev/null -w "%{http_code}" -H "Host: mail.posheapp.ir" http://localhost:8000/webmail || echo "000")
printf 'Mail webmail status: %s\n' "$MAIL_CODE"

if [ "$HTTP_CODE" = "200" ] || [ "$HTTP_CODE" = "401" ]; then
  log "Deploy successful"
else
  log "Deploy finished but API returned $HTTP_CODE"
  log "Debug: docker compose logs app --tail=50"
fi

cat <<EOF

Next steps:
  - Deploy from main: ./scripts/deploy.sh main
  - Platform admin panel: https://panel.posheapp.ir/login
  - Email setup: cp docker/mail/secrets.env.example docker/mail/secrets.env && ./scripts/setup-mail.sh
  - Fix broken mail: ./scripts/fix-mail-restart.sh  or  ./scripts/fix-site-and-mail.sh
  - Test email: docker compose exec app php artisan system:mail-test you@example.com
  - Mail status: ./scripts/mail-status.sh
  - Cafe Bazaar IAP: ./scripts/set-cafe-bazaar-env.sh 'JWT_FROM_PANEL'
    or: CAFE_BAZAAR_API_TOKEN='JWT' ./scripts/deploy.sh
  - After token change: docker compose exec app php artisan config:clear
  - Run scheduler: docker compose up -d scheduler
  - Seed contracts: docker compose exec app php artisan db:seed --class=ContractTemplateSeeder --force
  - SMS (MaxSMS panel): IPPANEL_API_MODE=jspd, IPPANEL_USERNAME, IPPANEL_PASSWORD, IPPANEL_OTP_PATTERN_CODE=qhhly1nai3njev0
  - SMS docs: docs/SMS-EDGE-ABROAD.md
  - SMS relay (Netherlands server): docs/SMS-RELAY.md
  - Fix SMS after deploy:    ./scripts/fix-sms-now.sh
  - Or: docker compose exec app php artisan system:sms-enable --fix
  - Probe + test send:       docker compose exec app php artisan system:sms-probe 09170577873 --send
  - OTP test mode (no SMS):  docker compose exec app php artisan system:sms-enable --log
  - Enable live SMS:         ./scripts/enable-live-sms.sh
  - Test SMS:             docker compose exec app php artisan system:sms-test 09170577873 --otp --debug
  - Check SMS status:     docker compose exec app php artisan system:sms-enable
  - Diagnose OTP/SMS:       ./scripts/diagnose-otp.sh
  - OTP trace log:          docker compose exec app tail -30 storage/logs/otp-sms.log
  - Site URL:            http://YOUR_SERVER_IP/  (or :8000)
  - Admin settings:       /admin/settings
  - If sms_mode=log only: login OTP code is 123456

EOF
