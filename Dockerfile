########################################
# Stage 1: Vendor
########################################
FROM composer:2 AS vendor

WORKDIR /app

COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --prefer-dist


########################################
# Stage 2: Runtime
########################################
FROM richarvey/nginx-php-fpm:php8.3

ENV WEBROOT=/var/www/html/public
ENV PHP_ERRORS_STDERR=1
ENV RUN_SCRIPTS=1
ENV REAL_IP_HEADER=1
ENV LOG_CHANNEL=stderr
ENV APP_ENV=production
ENV APP_DEBUG=false

WORKDIR /var/www/html

# Fix intl
RUN apk add --no-cache icu-dev \
    && docker-php-ext-install intl

COPY . /var/www/html
COPY --from=vendor /app/vendor /var/www/html/vendor

RUN composer dump-autoload \
    --no-dev \
    --optimize

RUN mkdir -p storage/logs bootstrap/cache

RUN chown -R www-data:www-data /var/www/html

RUN chmod -R 775 storage bootstrap/cache

EXPOSE 80
