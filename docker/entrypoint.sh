#!/bin/sh
set -e

if [ ! -d "/var/www/storage/app/private" ]; then
    mkdir -p /var/www/storage/app/{private,public}
    chown -R www-data:www-data /var/www/storage/app
fi

# Run migrations
php artisan migrate

# Pre-cache config and routes
php artisan config:cache
php artisan route:cache

exec "$@"
