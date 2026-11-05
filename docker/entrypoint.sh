#!/bin/sh
set -e
cd /var/www/html
mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views \
         storage/logs storage/app/public storage/app/private bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
if [ -z "$APP_KEY" ] && ! grep -qs '^APP_KEY=base64:' .env; then
    echo "ERROR: falta APP_KEY."
    exit 1
fi
if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
    echo "Ejecutando migraciones..."
    php artisan migrate --force
fi
if [ "$APP_ENV" = "production" ]; then
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
else
    php artisan config:clear > /dev/null
    php artisan route:clear > /dev/null
    php artisan view:clear > /dev/null
fi
exec "$@"