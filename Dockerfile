# ---------- Stage 1: Composer ----------
    FROM composer:2 AS vendor

    WORKDIR /app

    COPY composer.json composer.lock ./

    RUN composer install \
        --no-dev \
        --no-scripts \
        --prefer-dist \
        --optimize-autoloader

    # ---------- Stage 2: App ----------
    FROM php:8.3-fpm-alpine

    # Cài nginx + lib cho pgsql
    RUN apk add --no-cache \
        nginx \
        postgresql-dev \
        libzip-dev \
        oniguruma-dev \
        icu-dev \
        bash

    # Cài extension PHP (THÊM pgsql ở đây 👇)
    RUN docker-php-ext-install \
        pdo \
        pdo_pgsql \
        pgsql \
        zip \
        intl

    # Copy composer
    COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

    WORKDIR /var/www/html

    # Copy source
    COPY . .

    # Copy vendor
    COPY --from=vendor /app/vendor /var/www/html/vendor

    # Optimize autoload
    RUN composer dump-autoload --no-dev --optimize

    # Copy nginx config
    COPY nginx.conf /etc/nginx/http.d/default.conf

    # Permission
    RUN chown -R www-data:www-data /var/www/html

    # Port Render
    EXPOSE 8080

    # Start
    CMD sh -c "php-fpm -D && nginx -g 'daemon off;'"
