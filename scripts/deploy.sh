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
  if [ ! -f backend/.env ]; then
    log "Creating backend/.env from example"
    cp backend/.env.example backend/.env
  fi

  $COMPOSE exec -T app sh -c '
    grep -q "^CACHE_STORE=" .env && sed -i "s/^CACHE_STORE=.*/CACHE_STORE=file/" .env || echo "CACHE_STORE=file" >> .env
    grep -q "^QUEUE_CONNECTION=" .env && sed -i "s/^QUEUE_CONNECTION=.*/QUEUE_CONNECTION=sync/" .env || echo "QUEUE_CONNECTION=sync" >> .env
    grep -q "^SESSION_DRIVER=" .env && sed -i "s/^SESSION_DRIVER=.*/SESSION_DRIVER=file/" .env || echo "SESSION_DRIVER=file" >> .env
    grep -q "^DB_HOST=" .env || echo "DB_HOST=mysql" >> .env
    grep -q "^DB_DATABASE=" .env || echo "DB_DATABASE=posheh" >> .env
    grep -q "^REDIS_HOST=" .env && sed -i "s/^REDIS_HOST=.*/REDIS_HOST=redis/" .env || echo "REDIS_HOST=redis" >> .env
    grep -q "^REDIS_PORT=" .env || echo "REDIS_PORT=6379" >> .env
    grep -q "^SMS_MODE=" .env && sed -i "s/^SMS_MODE=.*/SMS_MODE=live/" .env || echo "SMS_MODE=live" >> .env
    grep -q "^SMS_PROVIDER=" .env || echo "SMS_PROVIDER=maxsms" >> .env
    grep -q "^IPPANEL_API_MODE=" .env || echo "IPPANEL_API_MODE=jspd" >> .env
  ' 2>/dev/null || true
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

  if ! git checkout -B "$BRANCH" "origin/$BRANCH" 2>/dev/null; then
    log "Cleaning untracked mobile/ files that block checkout (local Flutter scaffold)"
    git clean -fd -- mobile/ 2>/dev/null || true
    git checkout -B "$BRANCH" "origin/$BRANCH" || fail "Could not checkout $BRANCH"
  fi

  git reset --hard "origin/$BRANCH"
}

log "1/9 Fetching code"
sync_code

log "2/9 Starting containers"
$COMPOSE up -d --build || fail "docker compose up failed"

wait_for_mysql

log "3/9 Ensuring database exists"
$COMPOSE exec -T mysql mysql -uroot -p"${MYSQL_ROOT_PASSWORD:-secret}" -e \
  "CREATE DATABASE IF NOT EXISTS posheh CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" \
  2>/dev/null || $COMPOSE exec -T mysql mysql -uroot -psecret -e \
  "CREATE DATABASE IF NOT EXISTS posheh CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" \
  || fail "Could not create database"

ensure_env_file

log "4/9 Running migrations"
$COMPOSE exec -T app php artisan config:clear --no-interaction || true
$COMPOSE exec -T app php artisan migrate --force --no-interaction \
  || fail "Migration failed — check: docker compose logs app"

log "5/9 Seeding settings, blog and demo data"
$COMPOSE exec -T app php artisan db:seed --class=SystemSettingsSeeder --force --no-interaction \
  || fail "SystemSettingsSeeder failed"
$COMPOSE exec -T app php artisan db:seed --class=BlogSeeder --force --no-interaction \
  || log "BlogSeeder warning (may already be seeded)"
$COMPOSE exec -T app php artisan db:seed --class=DatabaseSeeder --force --no-interaction \
  || log "DatabaseSeeder warning (may already be seeded)"

log "6/9 Clearing caches and enabling SMS"
$COMPOSE exec -T app php artisan optimize:clear --no-interaction || true
$COMPOSE exec -T app php artisan cache:clear --no-interaction || true
$COMPOSE exec -T app php artisan config:clear --no-interaction || true
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
  - Set OTP pattern line in .env: IPPANEL_OTP_FROM_NUMBER=+9810008721297974
  - OTP also needs: IPPANEL_USERNAME, IPPANEL_PASSWORD, IPPANEL_OTP_PATTERN_CODE=qhhly1nai3njev0
  - Enable SMS (if needed): docker compose exec app php artisan system:sms-enable --live --from-env
  - Test SMS:             docker compose exec app php artisan system:sms-test 09170577873 --otp --debug
  - Check SMS status:     docker compose exec app php artisan system:sms-enable
  - OTP logs:             docker compose exec app tail -50 storage/logs/laravel.log
  - Login super admin:    09170577873
  - Admin settings:       /admin/settings
  - If sms_mode=log only: login OTP code is 123456

EOF
