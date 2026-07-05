#!/bin/sh
set -e

cd "$(dirname "$0")/.."

echo "=== Posheh server fix ==="

echo "1. Creating database if missing..."
docker compose exec mysql mysql -uroot -p"${MYSQL_ROOT_PASSWORD:-secret}" -e \
  "CREATE DATABASE IF NOT EXISTS posheh CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" \
  2>/dev/null || docker compose exec mysql mysql -uroot -psecret -e \
  "CREATE DATABASE IF NOT EXISTS posheh CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

echo "2. Fixing .env cache settings..."
docker compose exec app sh -c "
  grep -q '^CACHE_STORE=' .env && sed -i 's/^CACHE_STORE=.*/CACHE_STORE=file/' .env || echo 'CACHE_STORE=file' >> .env
  grep -q '^QUEUE_CONNECTION=' .env && sed -i 's/^QUEUE_CONNECTION=.*/QUEUE_CONNECTION=sync/' .env || echo 'QUEUE_CONNECTION=sync' >> .env
  grep -q '^SESSION_DRIVER=' .env && sed -i 's/^SESSION_DRIVER=.*/SESSION_DRIVER=file/' .env || echo 'SESSION_DRIVER=file' >> .env
"

echo "3. Clearing config cache..."
docker compose exec app php artisan config:clear --no-interaction || true

echo "4. Running migrations and seed..."
docker compose exec app php artisan migrate --seed --force --no-interaction

echo "5. Restarting app and nginx..."
docker compose restart app queue nginx

echo "6. Testing API..."
sleep 8
curl -s -o /dev/null -w "OTP endpoint HTTP status: %{http_code}\n" \
  -X POST http://localhost:8000/api/v1/auth/otp/send \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"mobile":"09121111111"}'

echo "=== Done ==="
echo "Demo login: 09121111111 / OTP: 123456 (if APP_ENV=local)"
