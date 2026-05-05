# ---------- Stage 1: Composer ----------
    FROM composer:2 AS vendor

    # Cài extension cần cho composer (intl cho Filament)
    RUN apk add --no-cache icu-dev \
        && docker-php-ext-install intl

    WORKDIR /app

    COPY composer.json composer.lock ./

    RUN composer install \
        --no-dev \
        --no-scripts \
        --prefer-dist \
        --optimize-autoloader


    # ---------- Stage 2: App ----------
    FROM php:8.3-fpm-alpine

    # Cài packages
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

    # Copy composer
    COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

    WORKDIR /var/www/html

    # Copy source
    COPY . .

    # Copy vendor
    COPY --from=vendor /app/vendor /var/www/html/vendor

    # Optimize Laravel
    RUN composer dump-autoload --no-dev --optimize \
        && php artisan config:cache || true \
        && php artisan route:cache || true \
        && php artisan view:cache || true

    # Permission
    RUN chown -R www-data:www-data /var/www/html \
        && chmod -R 775 storage bootstrap/cache || true

    # Nginx config
    COPY nginx.conf /etc/nginx/http.d/default.conf

    # Port Render
    EXPOSE 8080

    # Start app
    CMD sh -c "php-fpm -D && nginx -g 'daemon off;'"
