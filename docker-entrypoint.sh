#!/bin/sh
set -e
cd /var/www/html

php artisan migrate --force

php-fpm -D
exec nginx -g 'daemon off;'
