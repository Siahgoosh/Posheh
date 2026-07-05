#!/bin/sh
set -e

cd "$(dirname "$0")/.."

echo "=== Posheh Deploy (final) ==="

BRANCH="${1:-cursor/final-platform-update-e117}"

echo "1. Pulling branch: $BRANCH"
git fetch origin
git checkout "$BRANCH"
git pull origin "$BRANCH"

echo "2. Database check..."
docker compose exec mysql mysql -uroot -p"${MYSQL_ROOT_PASSWORD:-secret}" -e \
  "CREATE DATABASE IF NOT EXISTS posheh CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" \
  2>/dev/null || docker compose exec mysql mysql -uroot -psecret -e \
  "CREATE DATABASE IF NOT EXISTS posheh CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

echo "3. Building containers..."
docker compose up -d --build

echo "4. Migrations (preserves saved settings)..."
docker compose exec app php artisan migrate --force --no-interaction

echo "5. Seed metadata only (won't overwrite API keys)..."
docker compose exec app php artisan db:seed --class=SystemSettingsSeeder --force --no-interaction
docker compose exec app php artisan db:seed --class=DatabaseSeeder --force --no-interaction 2>/dev/null || true

echo "6. Clear caches..."
docker compose exec app php artisan config:clear --no-interaction
docker compose exec app php artisan cache:clear --no-interaction

echo "7. Building frontend..."
cd frontend && npm ci && npm run build && cd ..

echo "8. Restarting nginx..."
docker compose restart nginx app queue

echo "9. Health check..."
sleep 5
curl -s -o /dev/null -w "API status: %{http_code}\n" http://localhost:8000/api/v1/plans

echo ""
echo "=== Deploy complete ==="
echo "Super admin: 09170577873"
echo "Admin panel: /admin/settings"
echo "Set sms_mode=live, API key, from_number (+983000505), then test SMS"
