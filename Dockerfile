########################################
# Stage 1: Build vendor (Composer)
########################################
FROM composer:2 AS vendor

WORKDIR /app

# Copy file composer trước để tận dụng cache
COPY composer.json composer.lock ./

# Install dependencies (production only)
RUN composer install \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --prefer-dist



########################################
# Stage 2: Runtime (Nginx + PHP-FPM)
########################################
FROM richarvey/nginx-php-fpm:8.3

# ===== ENV =====
ENV WEBROOT=/var/www/html/public
ENV PHP_ERRORS_STDERR=1
ENV RUN_SCRIPTS=1
ENV REAL_IP_HEADER=1
ENV LOG_CHANNEL=stderr
ENV APP_ENV=production
ENV APP_DEBUG=false

WORKDIR /var/www/html

# ===== FIX: Install required PHP extensions =====
RUN apk add --no-cache \
        icu-dev \
    && docker-php-ext-install intl

# ===== Copy source code =====
COPY . /var/www/html

# ===== Copy vendor từ stage build =====
COPY --from=vendor /app/vendor /var/www/html/vendor

# ===== Optimize autoload =====
RUN composer dump-autoload \
    --no-dev \
    --optimize

# ===== Laravel directories =====
RUN mkdir -p storage/logs \
    && mkdir -p bootstrap/cache

# ===== Permissions =====
RUN chown -R www-data:www-data /var/www/html

RUN chmod -R 775 storage \
    && chmod -R 775 bootstrap/cache

# ===== Opcache =====
RUN echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/opcache.ini \
 && echo "opcache.memory_consumption=128" >> /usr/local/etc/php/conf.d/opcache.ini \
 && echo "opcache.max_accelerated_files=20000" >> /usr/local/etc/php/conf.d/opcache.ini \
 && echo "opcache.validate_timestamps=0" >> /usr/local/etc/php/conf.d/opcache.ini

EXPOSE 80
