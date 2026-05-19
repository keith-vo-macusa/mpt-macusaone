#!/bin/sh

set -e

cd /var/www/html

# tạo .env nếu chưa có
if [ ! -f .env ]; then
cat > .env <<EOF
APP_NAME=Laravel
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=http://localhost

LOG_CHANNEL=stderr
LOG_LEVEL=debug

DB_CONNECTION=sqlite
DB_DATABASE=/var/www/html/storage/database/database.sqlite

CACHE_STORE=file
QUEUE_CONNECTION=database
SESSION_DRIVER=file
EOF
fi

# generate key nếu chưa có
php artisan key:generate --force

# migrate
php artisan migrate --force

# cache
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

php-fpm -D

nginx -g "daemon off;"
