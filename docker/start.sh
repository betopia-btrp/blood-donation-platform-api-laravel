#!/bin/sh

# Run migrations
php /var/www/artisan migrate --force

# Cache config for production
php /var/www/artisan config:cache
php /var/www/artisan route:cache

# Start PHP-FPM in background
php-fpm -D

# Start Nginx in foreground
nginx -g "daemon off;"