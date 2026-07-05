#!/bin/sh

cd /var/www

if [ ! -f .env ] && [ -f .env.example ]; then
    cp .env.example .env
fi

if [ -f .env ]; then
    if ! grep -q '^APP_KEY=' .env; then
        echo 'APP_KEY=' >> .env
    fi

    if [ -d vendor ] && ! grep -q '^APP_KEY=base64:' .env; then
        php artisan key:generate --force --no-interaction || true
    fi
fi

mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
chmod -R ug+rwx storage bootstrap/cache 2>/dev/null || true

if [ -d vendor ] && [ -f artisan ]; then
    if [ -f scripts/ensure-database.php ]; then
        echo "Waiting for MySQL..."
        i=0
        while [ "$i" -lt 30 ]; do
            if php scripts/ensure-database.php >/dev/null 2>&1; then
                break
            fi
            i=$((i + 1))
            sleep 2
        done

        php scripts/ensure-database.php || echo "Warning: could not ensure database exists."
    fi

    php artisan config:clear --no-interaction 2>/dev/null || true
    php artisan migrate --force --no-interaction || echo "Warning: migrations failed."
fi

exec docker-php-entrypoint "$@"
