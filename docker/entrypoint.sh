#!/bin/sh
set -e

echo "==> Starting UD Fadlan Inventory deployment..."

# Update Nginx port if PORT environment variable is provided by hosting provider
if [ -n "$PORT" ]; then
    echo "==> Setting Nginx port to $PORT"
    sed -i "s/listen 80;/listen $PORT;/g" /etc/nginx/http.d/default.conf
fi

# Ensure storage directories and log file exist
mkdir -p /var/www/html/storage/framework/sessions
mkdir -p /var/www/html/storage/framework/views
mkdir -p /var/www/html/storage/framework/cache
mkdir -p /var/www/html/storage/logs
mkdir -p /var/www/html/bootstrap/cache
touch /var/www/html/storage/logs/laravel.log

# Create storage symlink
echo "==> Linking storage..."
php artisan storage:link || true

# Run database migrations
echo "==> Running database migrations..."
php artisan migrate --force || true

# Cache configurations, routes, and views for production performance
echo "==> Optimizing Laravel cache..."
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

# Fix permissions for www-data (make sure web server can read/write logs, cache, sessions)
echo "==> Setting storage permissions..."
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 777 /var/www/html/storage /var/www/html/bootstrap/cache

echo "==> Starting PHP-FPM and Nginx..."
php-fpm -D
nginx -g "daemon off;"
