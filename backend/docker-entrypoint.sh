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

exec docker-php-entrypoint "$@"
