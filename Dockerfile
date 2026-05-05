########################################
# Stage 1: Vendor (Composer chạy PHP 8.3)
########################################
FROM php:8.3-cli-alpine AS vendor

# Cài system libs cần thiết
RUN apk add --no-cache \
    git \
    unzip \
    icu-dev

# Cài composer từ image chính thức
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Copy file composer trước để tận dụng cache
COPY composer.json composer.lock ./

# Install dependencies (không dev)
RUN composer install \
    --no-dev \
    --no-scripts \
    --prefer-dist \
    --optimize-autoloader


########################################
# Stage 2: App (PHP-FPM + Nginx)
########################################
FROM php:8.3-fpm-alpine

# Cài packages hệ thống
RUN apk add --no-cache \
    nginx \
    postgresql-dev \
    libzip-dev \
    oniguruma-dev \
    icu-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    bash

# Config GD
RUN docker-php-ext-configure gd \
    --with-freetype \
    --with-jpeg

# Cài PHP extensions
RUN docker-php-ext-install \
    pdo \
    pdo_pgsql \
    pgsql \
    mbstring \
    bcmath \
    zip \
    intl \
    gd \
    exif \
    pcntl

# Copy composer (để chạy optimize)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy source code
COPY . .

# Copy vendor từ stage 1
COPY --from=vendor /app/vendor /var/www/html/vendor

# Optimize Laravel
RUN composer dump-autoload --no-dev --optimize \
    && php artisan config:cache || true \
    && php artisan route:cache || true \
    && php artisan view:cache || true

# Phân quyền
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 storage bootstrap/cache || true

# Copy nginx config
COPY nginx.conf /etc/nginx/http.d/default.conf

# Port cho Render
EXPOSE 8080

# Start services
CMD sh -c "php-fpm -D && nginx -g 'daemon off;'"
