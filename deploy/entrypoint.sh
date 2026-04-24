#!/usr/bin/env bash
set -euo pipefail

cd /var/www/html

: "${APP_ENV:=production}"
: "${APP_DEBUG:=false}"

if [ -z "${APP_KEY:-}" ] || [ "$APP_KEY" = "" ]; then
  echo "APP_KEY is missing; generating one..."
  php artisan key:generate --force
fi

if [ ! -L "/var/www/html/public/storage" ]; then
  php artisan storage:link
fi

php artisan config:clear || true
php artisan route:clear || true
php artisan view:clear || true

php artisan config:cache
php artisan route:cache
php artisan view:cache

if [ "${RUN_MIGRATIONS:-0}" = "1" ]; then
  php artisan migrate --force
fi

php -r "file_put_contents('php://stderr','Games app booted' . PHP_EOL);"

exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
