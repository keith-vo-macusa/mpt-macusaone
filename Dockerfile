########################################
# Stage 1: Vendor (Composer)
########################################
FROM php:8.3-cli-alpine AS vendor

# Cài lib cần thiết cho extension
RUN apk add --no-cache \
    git \
    unzip \
    icu-dev \
    libzip-dev

# Cài PHP extensions cho composer
RUN docker-php-ext-install \
    intl \
    zip

# Cài composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-scripts \
    --prefer-dist \
    --optimize-autoloader


########################################
# Stage 2: App (Runtime)
########################################
FROM php:8.3-fpm-alpine

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

RUN docker-php-ext-configure gd \
    --with-freetype \
    --with-jpeg

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
    pcntl \
    opcache

RUN printf '%s\n' \
    'opcache.enable=1' \
    'opcache.memory_consumption=256' \
    'opcache.interned_strings_buffer=16' \
    'opcache.max_accelerated_files=20000' \
    'opcache.validate_timestamps=0' \
    'opcache.revalidate_freq=0' \
    'opcache.save_comments=1' \
    'opcache.enable_cli=0' \
    > /usr/local/etc/php/conf.d/99-opcache.ini

# composer để optimize
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .
COPY --from=vendor /app/vendor /var/www/html/vendor

RUN composer dump-autoload \
    && php artisan config:cache || true \
    && php artisan route:cache || true \
    && php artisan view:cache || true

RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 storage bootstrap/cache || true \
    && chmod +x /var/www/html/docker-entrypoint.sh

COPY nginx.conf /etc/nginx/http.d/default.conf

EXPOSE 8080

ENTRYPOINT ["/var/www/html/docker-entrypoint.sh"]
