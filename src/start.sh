#!/bin/sh
php /var/www/artisan migrate --force
php /var/www/artisan config:cache
php /var/www/artisan route:cache
php-fpm -D
nginx -g "daemon off;"