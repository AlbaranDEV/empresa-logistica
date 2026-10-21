#!/bin/sh
set -e

cd /var/www/html

# Instala dependencias si aún no existen (primer arranque)
if [ -f composer.json ] && [ ! -d vendor ]; then
    composer install --no-interaction --prefer-dist
fi

if [ -f artisan ]; then
    if [ ! -f .env ] && [ -f .env.example ]; then
        cp .env.example .env
    fi
    if ! grep -q "^APP_KEY=base64" .env 2>/dev/null; then
        php artisan key:generate --force
    fi
    chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
fi

exec "$@"
