#!/bin/sh
set -e

cd /var/www

if [ ! -f .env ] && [ -f .env.example ]; then
    cp .env.example .env
fi

if [ -f .env ]; then
    if ! grep -q '^APP_KEY=' .env; then
        echo 'APP_KEY=' >> .env
    fi

    if [ -d vendor ] && ! grep -q '^APP_KEY=base64:' .env; then
        php artisan key:generate --force --no-interaction
    fi
fi

mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
chmod -R ug+rwx storage bootstrap/cache 2>/dev/null || true

if [ -d vendor ] && [ -f artisan ]; then
  echo "Waiting for MySQL..."
  i=0
  while [ "$i" -lt 30 ]; do
    if php scripts/ensure-database.php >/dev/null 2>&1; then
      break
    fi
    i=$((i + 1))
    sleep 2
  done

  echo "Ensuring database exists..."
  php scripts/ensure-database.php

  echo "Running database migrations..."
  php artisan migrate --force --no-interaction
fi

exec docker-php-entrypoint "$@"
