# ===================================================
# Stage 1: Build Frontend Assets (Vite)
# ===================================================
FROM node:20-alpine AS frontend
WORKDIR /app

# Copy dependency definitions
COPY package*.json ./
RUN npm ci

# Copy project files & build assets
COPY . .
RUN npm run build

# ===================================================
# Stage 2: PHP 8.4 Application & Nginx
# ===================================================
FROM php:8.4-fpm-alpine

# Install system dependencies and Nginx
RUN apk add --no-cache \
    nginx \
    git \
    curl \
    libpng-dev \
    libxml2-dev \
    zip \
    unzip \
    freetype-dev \
    libjpeg-turbo-dev \
    libzip-dev \
    oniguruma-dev \
    icu-dev

# Install PHP extensions required by Laravel 12 & DomPDF
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        pdo_mysql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        intl \
        zip \
        opcache

# Copy Composer binary from official image
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy application source code
COPY . .

# Copy compiled frontend assets from Stage 1
COPY --from=frontend /app/public/build ./public/build

# Install PHP production dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Setup Nginx configuration
COPY docker/nginx.conf /etc/nginx/http.d/default.conf

# Setup entrypoint script (remove any Windows CRLF line endings)
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN sed -i 's/\r$//' /usr/local/bin/entrypoint.sh && chmod +x /usr/local/bin/entrypoint.sh

# Set directory permissions for Laravel
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
